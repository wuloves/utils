<?php


namespace Exception;


class CollectException
{

    public function __construct($throwable, $key = 'crontab', $ext = null)
    {
        $returnError = null;
        $trace = $throwable->getTrace();
        $errorFile = $throwable->getFile();
        $errorLine = $throwable->getLine();
        if (strpos($errorFile, 'bin/hyperf.php') !== false || strpos($errorFile, 'vendor/') !== false) {
            $index = -1;
            do {
                $index++;
                if (empty($trace[$index])) {
                    break;
                }
                if (empty($trace[$index]['file'])) {
                    continue;
                }
                if (strpos($trace[$index]['file'], 'bin/hyperf.php') === false
                    && strpos($trace[$index]['file'], 'vendor/') === false) {
                    $errorFile = $trace[$index]['file'];
                    $errorLine = $trace[$index]['line'];
                    break;
                }
            } while ($index < 10);
        }
        if ($key === 'http') {
            $code = $throwable->getCode();
            $code = empty($code) ? 500 : $code;
            $e = [
                'message' => $throwable->getMessage(),
                'status_code' => $code,
                'code' => $throwable->getCode(),
                'error_path' => $errorFile . ':' . $errorLine,
                'trace' => $trace,
            ];
            /*
            if ($throwable instanceof \App\Exception\JWTAuthException) {
                $code = 401;
            }
            */
            if (in_array($code, [401, 422])) {
                return \Hyperf\Utils\ApplicationContext::getContainer()->get(\Hyperf\HttpServer\Contract\ResponseInterface::class)->json($e)->withStatus($code);
            }
            $returnError = \Hyperf\Utils\ApplicationContext::getContainer()->get(\Hyperf\HttpServer\Contract\ResponseInterface::class)->json($e)->withStatus(500);
        }
        $errorType = '';
        $redisKey = $key;
        if ($throwable instanceof \Exception) {
            $errorType = 'Exception';
            $redisKey .= '_exception_error';
        } else if ($throwable instanceof \Throwable) {
            $errorType = 'Throwable';
            $redisKey .= '_throwable_error';
        } else {
            return;
        }
        $e = [
            'getMessage' => $throwable->getMessage(),
            'errorType' => $errorType,
            'getCode' => $throwable->getCode(),
            'getFile' => $errorFile,
            'getLine' => $errorLine,
            'getExt' => $ext,
            'getFrom' => $key,
            'getTrace' => [],
        ];
        $e['getTrace'] = [];
        foreach ($trace as $item) {
            $e['getTrace'][] = json_encode($item, 256);
        }
        $e = json_decode(json_encode($e, 256), true);
        echo "\n\033[0;41;37m" . "ERROR AT [" . date("Y-m-d H:i:s") . "] \n"
            . "----------------------------------- [ " . $errorType . " ERROR START ] ---------------------------------\n";
        var_dump($e);
        echo "\n----------------------------------- [ " . $errorType . " ERROR END-- ] ---------------------------------\n\033[0m\n";
        var_dump('composer 错误打印');
        return $returnError;

    }


}
