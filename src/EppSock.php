<?php

namespace EppClient;


class EppSock
{

    private ?int $_port;
    private ?int $_timeout = 120;
    private ?int $_maxRedirects = 4;
    private ?bool $_post = false;
    private ?bool $_followLocation = true;
    private ?bool $_binaryTransfer = false;
    private ?bool $_connected = false;
    private ?string $_useragent = 'EppSock';
    private ?string $_certFile;
    private ?string $_cookieFileLocation;
    private ?string $_interface = "";
    private ?array $_postHeaders = ['Expect:'];
    private ?array $_logEntries = null;
    private mixed $_debugFile = false;
    private mixed $_referer;
    private mixed $_status;
    private mixed $_headers;
    private mixed $_body;
    private mixed $_error;
    private mixed $_sslContext;
    private mixed $_connection;
    private mixed $_blocking;

    public function __construct(
        private ?string $_url,
        private ?string $_authName = null,
        private ?string $_authPass = null,
        private ?string $_cookie = '/tmp',
        private mixed $_verify_peer = null,
        private mixed $_verify_peer_name = null,
        private mixed $_local_cert_path = null,
        private mixed $_local_cert_pwd = null,
        private mixed $_allow_self_signed = null
    ) {
        if (function_exists('posix_getuid')) {
            $this->_cookieFileLocation = $this->_cookie . '/url_' . md5($this->_url) . '-uid_' . posix_getuid() . '-cookie.txt';
        } else {
            $this->_cookieFileLocation = $this->_cookie . '/url_' . md5($this->_url) . '-uid_' . get_current_user() . '-cookie.txt';
        }
        if (file_exists($this->_cookieFileLocation)) {
            if (!is_writeable($this->_cookieFileLocation)) {
                throw new EppException(message: "FATAL ERROR: cookie file '" . $this->_cookieFileLocation . "' exists and is NOT writeable\n");
            }
        } else {
            if (!is_writeable(dirname($this->_cookieFileLocation))) {
                throw new EppException(message: "FATAL ERROR: cookie file FOLDER '" . dirname($this->_cookieFileLocation) . "' is NOT writeable\n");
            }
        }
    }

    /**
     * Client Cert
     *
     * @param string $action
     * @param string|null $certFile
     * @return mixed
     */
    public function ClientCert(?string $action = null, ?string $certFile = null)
    {
        if ($action === 'set') {
            $this->_certFile = $certFile;
        }
        return $this->_certFile;
    }

    /**
     * Interface
     *
     * @param string|null $action
     * @param string|null $interface
     * @return mixed
     */
    public function Interface(?string $action = null, ?string $interface = null)
    {
        if ($action === 'set') {
            $this->_interface = $interface;
        }
        return $this->_interface;
    }

    /**
     * DebugFile
     *
     * @param string|null $action
     * @param string|null $file
     * @return mixed
     */
    public function DebugFile(?string $action = null, ?string $file = null)
    {
        if ($action === 'set') {
            if (is_writeable((file_exists($file) ? $file : dirname($file)))) {
                $this->_debugFile = fopen($file, 'a+');
            } else {
                exit("FATAL ERROR: debug file '" . $file . "' is NOT writeable\n");
            }
        }
        return $this->_debugFile;
    }

    /**
     * MaxRedirects
     *
     * @param string|null $action
     * @param integer|null $maxRedirects
     * @return mixed
     */
    public function MaxRedirects(?string $action = null, ?int $maxRedirects = null)
    {
        if ($action === 'set') {
            $this->_maxRedirects = (int) $maxRedirects;
        }
        return $this->_maxRedirects;
    }

    /**
     * Timeout
     *
     * @param string|null $action
     * @param integer|null $timeout
     * @return mixed
     */
    public function Timeout(?string $action = null, ?int $timeout = null)
    {
        if ($action === 'set') {
            $this->_timeout = (int) $timeout;
        }
        return $this->_timeout;
    }

    /**
     * Referer
     *
     * @param string|null $action
     * @param mixed $referer
     * @return mixed
     */
    public function Referer(?string $action = null, mixed $referer = null)
    {
        if ($action === 'set') {
            $this->_referer = $referer;
        }
        return $this->_referer;
    }

    /**
     * Cookie
     *
     * @param string|null $action
     * @param string|null $path
     * @return mixed
     */
    public function Cookie(?string $action = null, ?string $path = null)
    {
        if ($action === 'set') {
            $this->_cookieFileLocation = $path;
        }
        return $this->_cookieFileLocation;
    }

    /**
     * BinaryTranfer
     *
     * @param string|null $action
     * @param boolean|null $binaryTransfer
     * @return bool
     */
    public function BinaryTransfer(?string $action = null, ?bool $binaryTransfer = false)
    {
        if ($action === 'set') {
            $this->_binaryTransfer = $binaryTransfer;
        }
        return $this->_binaryTransfer;
    }

    /**
     * FollowLocation
     *
     * @param string|null $action
     * @param boolean|null $followLocation
     * @return bool
     */
    public function FollowLocation(?string $action = null, ?bool $followLocation = false)
    {
        if ($action === 'set') {
            $this->_post = $followLocation;
        }
        return $this->_post;
    }

    /**
     * Post
     *
     * @param string|null $action
     * @param boolean|null $post
     * @return bool
     */
    public function Post(?string $action = null, ?bool $post = false)
    {
        if ($action === 'set') {
            $this->_post = $post;
        }
        return $this->_post;
    }

    /**
     * Url
     *
     * @param string|null $action
     * @param string|null $url
     * @return string
     */
    public function Url(?string $action = null, ?string $url = null)
    {
        if ($action === 'set') {
            $this->_url = $url;
        }
        return $this->_url;
    }

    /**
     * Post
     *
     * @param string|null $action
     * @param integer|null $port
     * @return int
     */
    public function Port(?string $action = null, ?int $port = null)
    {
        if ($action === 'set') {
            $this->_port = $port;
        }
        return $this->_port;
    }

    /**
     * UserAgent
     *
     * @param string|null $action
     * @param mixed $userAgent
     * @return mixed
     */
    public function UserAgent(?string $action = null, mixed $userAgent = null)
    {
        if ($action === 'set') {
            $this->_useragent = $userAgent;
        }
        return $this->_useragent;
    }

    /**
     * Headers
     *
     * @param string|null $action
     * @param array|null $headers
     * @return array
     */
    public function Headers(?string $action = null, ?array $headers = null)
    {
        if ($action === 'set') {
            $this->_postHeaders = array_merge($this->_postHeaders, (array) $headers);
        }
        return $this->_postHeaders;
    }

    /**
     * Http
     *
     * @param string|null $action
     * @return void
     */
    public function Http(?string $action)
    {
        if (strtolower($action) === 'status') {
            return $this->_status;
        } else if (strtolower($action) === 'headers') {
            return $this->_headers;
        } else if (strtolower($action) === 'body') {
            return $this->_body;
        } else if (strtolower($action) === 'error') {
            return $this->_error;
        } else {
            throw new EppException(message: 'Invalid action: "' . htmlspecialchars($action) . '"');
        }
    }

    /**
     * Fork parent __tostring
     *
     * @return mixed
     */
    public function __tostring()
    {
        return $this->_body;
    }

    /**
     * Write Log
     *
     * @param string|null $text
     * @param string|null $action
     * @return mixed
     */
    protected function writeLog(?string $text, ?string $action)
    {
        if ($this->_debugFile) {
            // Hide userid in the logging
            $text = $this->hideTextBetween(text: $text, start: '<clID>', end: '</clID>');
            // Hide password in the logging
            $text = $this->hideTextBetween(text: $text, start: '<pw>', end: '</pw>');
            $text = $this->hideTextBetween(text: $text, start: "<pw><![CDATA[', ']]>", end: '</pw>');
            // Hide new password in the logging
            $text = $this->hideTextBetween(text: $text, start: '<newPW>', end: '</newPW>');
            $text = $this->hideTextBetween(text: $text, start: "<newPW><![CDATA[', ']]>", end: '</newPW>');
            // Hide domain password in the logging
            $text = $this->hideTextBetween(text: $text, start: '<domain:pw>', end: '</domain:pw>');
            $text = $this->hideTextBetween(text: $text, start: "<domain:pw><![CDATA[', ']]>", end: '</domain:pw>');
            // Hide contact password in the logging
            $text = $this->hideTextBetween(text: $text, start: '<contact:pw>', end: '</contact:pw>');
            $text = $this->hideTextBetween(text: $text, start: "<contact:pw><![CDATA[', ']]>", end: '</contact:pw>');
            //echo "-----".date("Y-m-d H:i:s")."-----".$text."-----end-----\n";
            $log = "-----" . $action . "-----" . date("Y-m-d H:i:s") . "-----\n" . $text . "\n-----END-----" . date("Y-m-d H:i:s") . "-----\n";
            $this->_logEntries[] = $log;
            if ($this->_debugFile) {
                file_put_contents($this->_debugFile, "\n" . $log, FILE_APPEND);
            }
        }
    }

    /**
     * Hide Text from element
     *
     * @param string|null $text
     * @param string|null $start
     * @param string|null $end
     * @return mixed
     */
    protected function hideTextBetween(?string $text, ?string $start, ?string $end)
    {
        if (($startpos = strpos(strtolower($text), strtolower($start))) !== false) {
            if (($endpos = strpos(strtolower($text), strtolower($end))) !== false) {
                $text = substr($text, 0, $startpos + strlen($start)) . 'XXXXXXXXXXXXXXXX' . substr($text, $endpos, 99999);
            }
        }
        return $text;
    }


    /**
     * Query
     *
     * @param mixed|null $postFields
     * @return mixed
     */
    public function query(mixed $postFields = null)
    {
        $target = sprintf('%s://%s:%d', ($ssl === true ? $protocol : 'tcp'), $host, $port);
        if (!$this->_sslContext) {
            $context = stream_context_create();
            stream_context_set_option($context, 'ssl', 'verify_peer', $this->_verify_peer);
            stream_context_set_option($context, 'ssl', 'verify_peer_name', $this->_verify_peer_name);
            if ($this->_local_cert_path) {
                stream_context_set_option($context, 'ssl', 'local_cert', $this->_local_cert_path);
                if (isset($this->_local_cert_pwd) && (strlen($this->_local_cert_pwd) > 0)) {
                    stream_context_set_option($context, 'ssl', 'passphrase', $this->_local_cert_pwd);
                }
                if (isset($this->_allow_self_signed)) {
                    stream_context_set_option($context, 'ssl', 'allow_self_signed', $this->_allow_self_signed);
                    stream_context_set_option($context, 'ssl', 'verify_peer', false);
                } else {
                    stream_context_set_option($context, 'ssl', 'verify_peer', $this->_verify_peer);
                }
            }
            $this->_sslContext = $context;
        }
        $this->_connection = stream_socket_client($this->_url . ':' . $this->_port, $errno, $errstr, $this->_timeout, STREAM_CLIENT_CONNECT, $this->_sslContext);
        if (is_resource($this->_connection)) {
            stream_set_blocking($this->_connection, $this->_blocking);
            stream_set_timeout($this->_connection, $this->_timeout);
            if ($errno == 0) {
                $meta = stream_get_meta_data($this->_connection);
                if (isset($meta['crypto'])) {
                    $this->writeLog(
                        text: "Stream opened to " . $this->Url(action: 'get') . " port " . $this->Port(action: 'get') . " with protocol " . $meta['crypto']['protocol'] . ", cipher " . $meta['crypto']['cipher_name'] . ", " . $meta['crypto']['cipher_bits'] . " bits " . $meta['crypto']['cipher_version'],
                        action: "Connection made"
                    );
                } else {
                    $this->writeLog(text: "Stream opened to " . $this->Url(action: 'get') . " port " . $this->Port(action: 'get'), action: "Connection made");
                }
                $this->_connected = true;
                $this->read();
            }
            return $this->_connected;
        } else {
            $this->writeLog(text: "Connection could not be opened: $errno $errstr", action: "ERROR");
            return false;
        }
    }
}
