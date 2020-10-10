<?php
require_once(__DIR__ . '/../SystemClock.php');

class Dummy
{
    const ENV_DEV = 'dev';

    public function isDev()
    {
        $env = 'dev';
        return $env === self::ENV_DEV;
    }

    public function isAjax()
    {
        return true;
    }
}

SystemClockUtil::setDebugFlag(Dummy::isDev());
SystemClockUtil::setFrontFlag(Dummy::isAjax());
SystemClockUtil::initializeClock();

?>
<html>
<head></head>
<body>
    <p>コンテンツ BEGIN</p>
    <p>NOW -> <?= SystemClockUtil::getNow() ?></p>
    <p>TODAY -> <?= SystemClockUtil::getNow('Y/m/d') ?></p>
    <p>コンテンツ END</p>
</body>
</html>
