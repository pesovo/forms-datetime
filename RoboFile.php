<?php

class RoboFile extends \Robo\Tasks
{

    public function __construct()
    {
        $this->stopOnFail(TRUE);
    }

    public function ciBuild()
    {
        $this->check();
    }

    public function ciAfter()
    {
        if ($this->hasXdebug()) {
            $this->taskExec('composer')
                ->arg('require')
                ->arg('satooshi/php-coveralls')
                ->arg('dev-master#2fbf803')
                ->option('--no-interaction')
                ->option('--prefer-source')
                ->option('--no-progress')
                ->run();

            $this->taskExec('vendor/bin/coveralls')
                ->option('--verbose')
                ->option('--config', 'build/.coveralls.yml')
                ->run();
        }
    }

    public function check()
    {
        $this->checkLint();
        $this->checkCs();
        $this->tests();
    }

    public function checkLint()
    {
        $this->taskExec('vendor/bin/parallel-lint')
            ->option('-e', 'php,phpt')
            ->arg('src')
            ->arg('tests')
            ->run();
    }

    public function checkCs()
    {
        $this->taskExec('vendor/bin/phpcs')
            ->option('-s')
            ->option('-p')
            ->option('--standard=vendor/nella/coding-standard/Nella/ruleset.xml')
            ->arg('src')
            ->arg('tests')
            ->run();
    }

    public function tests()
    {
        if ($this->hasXdebug()) {
            $this->testsWithCoverage();
        } else {
            $this->testsWithoutCoverage();
        }
    }

    public function testsWithoutCoverage()
    {
        $this->taskTester()
            ->run();
    }

    public function testsWithCoverage()
    {
        $this->taskTester()
            ->option('--coverage', 'build/clover.xml')
            ->option('--coverage-src', 'src')
            ->run();
    }

    /**
     * @return \Robo\Task\Base\Exec
     */
    private function taskTester()
    {
        return $this->taskExec('vendor/bin/tester')
            ->option('-s')
            ->option('-c', 'tests/php.ini')
            ->option('-p', 'php')
            ->arg('tests');
    }

    /**
     * @return bool
     */
    private function hasXdebug()
    {
        return !defined('HHVM_VERSION') && PHP_MAJOR_VERSION !== 7;
    }

}
