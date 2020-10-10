<?php

interface SystemClockInterface
{
    const DEFAULT_FORMAT = 'Y-m-d H:i:s';
    const DEFAULT_TIME_STRING = 'now';

    /**
     * @param string $format
     * @return string
     */
    public function getNow($format = self::DEFAULT_FORMAT);
}

class RealTimeSystemClock implements SystemClockInterface
{
    /**
     * @param string $format
     * @return string
     */
    public function getNow($format = self::DEFAULT_FORMAT)
    {
        return date_create()->format($format);
    }
}

class DebugSystemClock implements SystemClockInterface
{
    /**
     * 型は好きにするといい
     */
    protected $now;

    protected $error = false;

    final public function __construct()
    {
        $this->initialize();
    }

    /**
     * This method is called the constructor.
     */
    protected function initialize()
    {
        $this->setNow(self::DEFAULT_TIME_STRING);
    }

    /**
     * @param string $format
     * @return string
     */
    public function getNow($format = self::DEFAULT_FORMAT)
    {
        return static::formatDateTime($this->now, $format);
    }

    /**
     * @param string $time
     */
    protected function setNow($time)
    {
        $this->errorOccur(false);
        $this->now = static::createDateTime($time);
        if (!$this->now) {
            $this->errorOccur();
            $this->now = static::createDateTime();
        }
    }

    /**
     * @param bool $error
     */
    protected function errorOccur($error = true)
    {
        $this->error = $error;
    }

    /**
     * @return boolean
     */
    public function isErrorOccured()
    {
        return $this->error;
    }

    /**
     * 日時オブジェクトを日時文字列に変換
     * @param DateTime $date_time
     * @param string $format
     */
    protected static function formatDateTime($date_time, $format = self::DEFAULT_FORMAT)
    {
        return $date_time->format($format);
    }

    /**
     * 日時文字列から日時オブジェクトを生成
     * @param string $time
     * @return DateTime|null
     */
    protected static function createDateTime($time = self::DEFAULT_TIME_STRING)
    {
        return date_create($time) ?: null;
    }
}

class CookieDebugSystemClock extends DebugSystemClock
{
    const PARAMETER_KEY = 'sytem_clock_debug_time';

    /**
     * This method is called the constructor.
     */
    protected function initialize()
    {
        $time = null;
        switch (true) {
            case isset($_GET) && array_key_exists(self::PARAMETER_KEY, $_GET):
                $time = $_GET[self::PARAMETER_KEY];
                break;
            case isset($_COOKIE) && array_key_exists(self::PARAMETER_KEY, $_COOKIE):
                $time = $_COOKIE[self::PARAMETER_KEY];
                break;
            default:
                // Cookie周りの操作が必要ないので
                $this->setNow(self::DEFAULT_TIME_STRING);
                return;
        }
        $this->setNow($time ?: self::DEFAULT_TIME_STRING);
        if ($this->isErrorOccured()) {
            $this->setCookie();
            throw new InvalidArgumentException("Parameter must be valid datetime.");
        }
        $this->setCookie($time);
    }

    private function setCookie($value = null)
    {
        $expire = $value ? 60 * 60 : -1;
        setcookie(self::PARAMETER_KEY, $value, time() + $expire);
    }
}

class SystemClockUtil
{
    private static $clock;
    private static $is_debug = false;
    private static $is_front = false;

    const DEFAULT_CLOCK_CLASS = 'RealTimeSystemClock';
    const DEBUG_CLOCK_CLASS = 'CookieDebugSystemClock';

    public static function setDebugFlag($is_debug = false)
    {
        self::$is_debug = $is_debug;
    }

    public static function setFrontFlag($is_front = false)
    {
        self::$is_front = $is_front;
    }

    public static function initializeClock()
    {
        $clock_class = self::DEFAULT_CLOCK_CLASS;
        if (self::$is_debug) {
            $clock_class = self::DEBUG_CLOCK_CLASS;
        }
        self::setClock(new $clock_class());
    }

    /**
     * @param string $format
     * @return string
     */
    public static function getNow($format = SystemClockInterface::DEFAULT_FORMAT)
    {
        return self::getClock()->getNow($format);
    }

    public static function getClock()
    {
        if (!self::$clock) {
            self::initializeClock();
        }
        return self::$clock;
    }

    private static function setClock(SystemClockInterface $clock)
    {
        self::$clock = $clock;
        if (!self::$is_debug) {
            // デバッグモードじゃなければ以下は処理しない
            return;
        }
        if (self::getNow()) {
            // 実際にgetNowか動く場合のみ、デバッグ表示する
            // 例外とか投げられると無限ループに陥る
            register_shutdown_function([get_class(), 'debugView']);
        }
    }

    public static function debugView()
    {
        if (!self::$is_front) {
            return;
        }
        $script = <<<'__STR__'
            <script>
            window.addEventListener('load', function (event) {
                let element = document.createElement("div");
                let text = document.createTextNode("DEBUG: %s");
                element.appendChild(text);
                document.body.appendChild(element);
            });
            </script>
__STR__;
        echo sprintf($script, self::getNow());
    }
}
