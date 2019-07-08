<?php
namespace Reaper;

/**
 * Class Config
 * @package Reaper
 *
 * Reads config from environments variables if present. If not,
 * env.php is used as a backup. Make sure this file mirrors the
 * .env file in the docker container!
 */

class Config
{
    private $configPath;

    public function __construct ()
    {
        $this->configPath = __DIR__ . '/../config/env.php';
    }

    /**
     * Sets global $_ENV variables from environment. Settings in env.php
     * are used as a backup if environment has not been set.
     */
    public function setEnvs ($configPath = false)
    {
        $this->configPath = $this->configPath ?: $configPath;
        if (file_exists($this->configPath)) {
            include $this->configPath;
            $this->envs = (array)$envs;
            foreach ($this->envs as $env => $value) {
                $this->setEnv($env, $value);
            }
        }
    }

    public function getEnv ($env)
    {
        $val = getenv($env);
        if ($val && empty($_ENV[$env])) {
            $_ENV[$env] = $val;
        }
        return $_ENV[$env] ?? null;
    }

    /*
     * Sets ENV if it hasn't been set before
     */
    private function setEnv ($env, $val)
    {
        if (empty($_ENV[$env])) {
            $_ENV[$env] = $val;
        }
    }

}