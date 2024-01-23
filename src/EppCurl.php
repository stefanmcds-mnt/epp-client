<?php

/**
 * A simple class handling HTTP sessions through cURL.
 * 
 * @category    src
 * @package     EppCurl
 * @author      STEF@N MCDS S.a.s. <info@stefan-mcds.it>
 * @license     http://opensource.org/licenses/bsd-license.php New BSD License
 * @version     1.0
 */

namespace EppClient;

class EppCurl
{
    private ?int $_timeout = 120;
    private ?int $_maxRedirects = 4;
    private ?bool $_post = false;
    private ?bool $_followLocation = true;
    private ?bool $_binaryTransfer = false;
    private ?string $_useragent = 'EppCurl';
    private ?string $_certFile;
    private ?string $_cookieFileLocation;
    private ?string $_interface = "";
    private ?string $cookie = '/tmp';
    private ?array $_postHeaders = ['Expect:'];
    private ?array $_logEntries = null;
    private ?array $_response = null;
    private mixed $_debugFile = false;
    private mixed $_referer;

    public function __construct(
        private ?string $server,
        private ?int $port = null,
        private ?string $username = null,
        private ?string $password = null,
    ) {
        if (function_exists('posix_getuid')) {
            $this->_cookieFileLocation = $this->cookie . '/url_' . md5($this->server) . '-uid_' . posix_getuid() . '-cookie.txt';
        } else {
            $this->_cookieFileLocation = $this->cookie . '/url_' . md5($this->server) . '-uid_' . get_current_user() . '-cookie.txt';
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
     * Class destructor
     */
    public function __destruct()
    {
        if ($this->_debugFile !== false) {
            fclose($this->_debugFile);
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
                throw new EppException(message: "FATAL ERROR: debug file '" . $file . "' is NOT writeable\n");
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
            $this->server = $url;
        }
        return $this->server;
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
            $this->port = $port;
        }
        return $this->port;
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
        if (isset($this->_response[$action])) {
            return $this->_response[$action];
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
        return $this->_response['body'];
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->server);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_postHeaders);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $this->_maxRedirects);
        if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->_followLocation);
        }
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->_cookieFileLocation);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_cookieFileLocation);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_useragent);
        curl_setopt($ch, CURLOPT_POST, $this->_post);
        if ($postFields != null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        if (!empty($this->port)) {
            curl_setopt($ch, CURLOPT_PORT, $this->port);
        }
        if (!empty($this->_interface)) {
            curl_setopt($ch, CURLOPT_INTERFACE, $this->_interface);
        }
        if (!empty($this->_referer)) {
            curl_setopt($ch, CURLOPT_REFERER, $this->_referer);
        }
        if ($this->_binaryTransfer) {
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        }
        if (!empty($this->username)) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }
        if (!empty($this->_certFile)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSLCERT, $this->_certFile);
        }
        if ($this->_debugFile !== false) {
            curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_STDERR, $this->_debugFile);
        }
        $response = curl_exec($ch);
        $this->_response['connected'] = true;
        $this->_response['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->_response['headers'] = substr($response, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $this->_response['body'] = substr($response, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
        $this->_response['error'] = ($response === false) ? curl_error($ch) : "";
        curl_close($ch);
        // write debug information
        if ($this->_debugFile) {
            $log = __FILE__ . " @ " . __LINE__ . " -- " . date("c") . "\n" .
                "==== START OUTPUT ====\n" .
                curl_getinfo($ch, CURLINFO_HEADER_OUT) . $postFields .
                "==== END OUTPUT ====\n" .
                "==== START INPUT ====\n" .
                $response .
                "==== END INPUT ====\n\n";
            $this->writeLog(text: $log, action: 'EppCurl');
        }
        return $this->_response;
    }
}
