<?php
/**
 * Standalone SMTP Client for XentraPOS
 * A lightweight alternative to PHPMailer for simple environment deployment.
 */
class XentraSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $encryption;
    private $from_email;
    private $from_name;
    private $socket;

    public function __construct($config) {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->user = $config['user'];
        $this->pass = $config['pass'];
        $this->encryption = strtolower($config['encryption']);
        $this->from_email = $config['from_email'];
        $this->from_name = $config['from_name'] ?? 'XentraPOS Notifications';
    }

    private function execute($command, $expectedResponse) {
        fputs($this->socket, $command . "\r\n");
        
        $response = '';
        while ($line = fgets($this->socket, 512)) {
            $response .= $line;
            // If 4th character is space, it's the last line of the response
            if (isset($line[3]) && $line[3] == ' ') {
                break;
            }
        }

        if (substr($response, 0, 3) != $expectedResponse) {
            throw new Exception("SMTP Error: $command -> $response");
        }
        return $response;
    }

    public function send($to, $subject, $body) {
        $host = ($this->encryption === 'ssl' ? 'ssl://' : '') . $this->host;
        $this->socket = fsockopen($host, $this->port, $errno, $errstr, 30);
        
        if (!$this->socket) throw new Exception("Could not connect to SMTP host: $errstr");

        // Read Initial Greeting (flush buffer)
        while ($line = fgets($this->socket, 512)) {
            if (isset($line[3]) && $line[3] == ' ') break;
        }

        $helo = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $this->execute("EHLO " . $helo, 250);

        if ($this->encryption === 'tls') {
            $this->execute("STARTTLS", 220);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("FAILED to negotiate TLS");
            }
            // After STARTTLS, EHLO must be sent again
            $this->execute("EHLO " . $helo, 250);
        }

        $this->execute("AUTH LOGIN", 334);
        $this->execute(base64_encode($this->user), 334);
        $this->execute(base64_encode($this->pass), 235);

        $this->execute("MAIL FROM: <$this->user>", 250);
        $this->execute("RCPT TO: <$to>", 250);
        $this->execute("DATA", 354);

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'To: ' . $to,
            'Subject: ' . $subject,
            'Date: ' . date('r'),
            'X-Mailer: XentraPOS SMTP Client'
        ];

        fputs($this->socket, implode("\r\n", $headers) . "\r\n\r\n");
        fputs($this->socket, $body . "\r\n");
        $this->execute(".", 250);
        $this->execute("QUIT", 221);

        fclose($this->socket);
        return true;
    }
}
