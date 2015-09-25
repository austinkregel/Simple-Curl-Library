<?php

/**
 * Class Curl
 */
class Curl
{
    /**
     * @var
     */
    public static $instance;

    /**
     * @var
     */
    private $url;
    /**
     * @var
     */
    private $curl_instance;
    /**
     * @var array Holds the variables to be pushed to the server,
     * whether using put, delete, post, or get.
     */
    private $payload;
    /**
     * @var array Holds any error messages
     */
    private $messages = [];
    /**
     * @var mixed Holds whatever the response was from the request
     */
    private $response_results;
    /**
     * @var array holds the defined curl variables
     */
    private $curl_variables;

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        if (empty(self::$instance))
            self::$instance = new static;
        return call_user_func_array([self::$instance, $method], $arguments);
    }

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array([self::$instance, $method], $arguments);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode(new CurlBag($this->response_results, $this->url), JSON_PRETTY_PRINT);

    }

    /**
     * @param array $payload
     * @return $this
     */
    public function withData(Array $payload)
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * @param null $options
     * @return $this
     */
    public function post($options = [])
    {
        $curl_opts = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $this->payload,
            CURLOPT_URL => $this->url,
            CURLOPT_SSL_VERIFYHOST => false,
        ];
        $opts = array_replace($curl_opts, $options);
        $this->curl_variables = $opts;
        curl_setopt_array($this->curl_instance, $curl_opts);
        return $this;
    }

    /**
     * @return mixed
     */
    public function send()
    {
        return $this->makeRequest();
    }

    /**
     * @return mixed
     */
    private function makeRequest()
    {
        $resource = $this->curl_instance;
        $return = curl_exec($resource);
        $this->messages[] = curl_error($resource);
        $this->messages[] = curl_errno($resource);
        curl_close($resource);
        $this->response_results = $return;
        return $this;
    }

    /**
     *
     */
    public function debug()
    {
        $this->messages[] = 'This request is being debugged';
        echo '<pre>';
        $this->var_debugged($this);
        echo '</pre>';
    }

    /**
     * @param $variable
     * @param int $strlen
     * @param int $width
     * @param int $depth
     * @param int $i
     * @param array $objects
     * @return string
     */
    public function
    var_debugged($variable, $strlen = -1, $width = 250, $depth = 100, $i = 0, &$objects =
    array())
    {
        $search = array("\0", "\a", "\b", "\f", "\n", "\r", "\t", "\v");
        $replace = array('\0', '\a', '\b', '\f', '\n', '\r', '\t',
            '\v');
        $string = '';
        switch (gettype($variable)) {
            case 'boolean':
                $string .= $variable ? 'true' : 'false';
                break;
            case 'integer':
                $string .= $variable;
                break;
            case 'double':
                $string .= $variable;
                break;
            case 'resource':
                $string .= '[resource]';
                break;
            case 'NULL':
                $string .= "null";
                break;
            case 'unknown type':
                $string .= gettype($variable);
                break;
            case 'string':
                $len = strlen($variable);
                $variable =
                    str_replace($search, $replace, substr($variable, 0, $strlen), $count);
                $variable = substr($variable, 0, $strlen);
                if ($len < $strlen) $string .= '"' . $variable . '"';
                else $string .= 'string(' . $len . '): "' . $variable . '"...';
                break;
            case 'array':
                $len = count($variable);
                if ($i == $depth) $string .= 'array(' . $len . ') {...}';
                elseif (!$len) $string .= 'array(0) {}';
                else {
                    $keys = array_keys($variable);
                    $spaces = str_repeat(' ', $i * 2);
                    $string .= "array($len)\n" . $spaces . '{';
                    $count = 0;
                    foreach ($keys as $key) {
                        if ($count == $width) {
                            $string .= "\n" . $spaces . "  ...";
                            break;
                        }
                        $string .= "\n" . $spaces . "  [$key] => ";
                        $string .=
                            $this->var_debugged($variable[$key], $strlen, $width, $depth, $i + 1, $objects);
                        $count++;
                    }
                    $string .= "\n" . $spaces . '}';
                }
                break;
            case 'object':
                $id = array_search($variable, $objects, true);
                if ($id !== false)
                    $string .= get_class($variable) . '#' . ($id + 1) . ' {...}';
                else if ($i == $depth)
                    $string .= get_class($variable) . ' {...}';
                else {
                    $id = array_push($objects, $variable);
                    $array = (array)$variable;
                    $spaces = str_repeat(' ', $i * 2);
                    $string .= get_class($variable) . "#$id\n" . $spaces . '{';
                    $properties = array_keys($array);
                    foreach ($properties as $property) {
                        $name = str_replace("\0", ':', trim($property));
                        $string .= "\n" . $spaces . "  [$name] => ";
                        $string .=
                            $this->var_debugged($array[$property], $strlen, $width, $depth, $i + 1, $objects);
                    }
                    $string .= "\n" . $spaces . '}';
                }
                break;
        }
        if ($i > 0) return $string;
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        do $caller = array_shift($backtrace); while ($caller &&
            !isset($caller['file']));
        if ($caller) $string =
            $caller['file'] . ':' . $caller['line'] . "\n" . $string;
        echo $string;
    }

    /**
     * @param null $url
     * @return $this
     */
    private function request($url = null)
    {
        $this->url = $url;
        $this->curl_instance = curl_init();
        return $this;
    }

    /**
     * @return string
     */
    private function toJson(){
        return $this->__toString();
    }
}

/**
 * Class CurlBag
 */
class CurlBag implements JsonSerializable{
    /**
     * @var
     */
    public $response;
    /**
     * @var
     */
    public $to_url;

    /**
     * @param $response
     * @param $to_url
     */
    public function __construct($response, $to_url){
        if($this->isValidXml($response))
            $this->response = new XmlToJson($response);
        else
            $this->response = $response;

        $this->to_url = $to_url;
    }

    /**
     * @return $this
     */
    function jsonSerialize()
    {
        return $this;
    }

    /**
     * @param $content
     * @return bool
     */
    public function isValidXml($content)
    {
        $content = trim($content);
        if (empty($content)) {
            return false;
        }
        //html go to hell!
        if (stripos($content, '<!DOCTYPE html>') !== false) {
            return false;
        }

        libxml_use_internal_errors(true);
        simplexml_load_string($content);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        return empty($errors);
    }

}

/**
 * Class XmlToJson
 */
class XmlToJson implements JsonSerializable{
    /**
     * @var
     */
    private $input;

    /**
     * @param $input
     */
    public function __construct ($input){
        $this->input = $input;
        return json_encode($this);
    }

    /**
     * @return SimpleXMLElement
     */
    function jsonSerialize()
    {
        return simplexml_load_string(
            trim(
                str_replace('"', "'",
                    str_replace(
                        array("\n", "\r", "\t"), '', $this->input)
                )
            )
        );
    }
}


echo Curl::request('https://wtfsi.xyz/test/post')
    ->withData([
        'some'=>'data',
        'should'=>'see',
        'this'=>'in',
        'response'
    ])
    ->post()
    ->send()
    ->toJson();
