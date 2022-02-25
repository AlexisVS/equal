<?php
/*
    This file is part of the eQual framework <http://www.github.com/cedricfrancoys/equal>
    Some Rights Reserved, Cedric Francoys, 2010-2021
    Licensed under GNU LGPL 3 license <http://www.gnu.org/licenses/>
*/
namespace equal\cron;

use equal\organic\Service;
use equal\services\Container;


class Scheduler extends Service {
    

    /**
     * This method cannot be called directly (should be invoked through Singleton::getInstance)
     */
    protected function __construct(Container $container) {
    }
    
    public static function constants() {
        return ['ROOT_USER_ID'];
    }

    /**
     * Run a batch of scheduled tasks.
     * #memo - Scheduler always operates as root user.
     */
    public function run() {
        $orm = $this->container->get('orm');

        $tasks_ids = $orm->search('core\Task', ['is_active', '=', true], ['id' => 'asc'], 0, 10);

        if($tasks_ids > 0) {
            $now = time();
            $tasks = $orm->read('core\Task', $tasks_ids, ['id', 'moment', 'is_recurring', 'repeat_axis', 'repeat_step', 'controller', 'params']);
            foreach($tasks as $tid => $task) {
                if($task['moment'] <= $now) {
                    // run
                    try {
                        $body = json_decode($task['params'], true);
                        \eQual::run('do', $task['controller'], $body, true);
                    }
                    catch(\Exception $e) {
                        // error occured during execution
                    }
                    // update task, if recurring
                    if($task['is_recurring']) {
                        $moment = strtotime("+{$task['repeat_step']} {$task['repeat_axis']}", $task['moment']);
                        if($moment < $now) {
                            $moment = strtotime("+{$task['repeat_step']} {$task['repeat_axis']}", $now);
                        }
                        $orm->write('core\Task', $tid, ['moment' => $moment]);
                    }
                    else {
                        $orm->remove('core\Task', $tid, true);
                    }
                }
            }
        }
    }

    /**
     * Run a batch of scheduled tasks.
     * #memo - Scheduler always operates as root user.
     * 
     * @param   string    $name         Name of the task to schedule, to ease task identification.
     * @param   integer   $moment       Timestamp of the moment of the first execution.
     * @param   string    $controller   Controller to invoker, with package notation.
     * @param   string    $params       JSON string holding the payload to relay to the controller.
     */
    public function schedule($name, $moment, $controller, $params, $recurring=false, $repeat_axis='day', $repeat_step='1') {
        $orm = $this->container->get('orm');
        trigger_error("scheduling job", E_USER_WARNING);

        try {
            json_decode($params);
            if(json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('invalid_json', QN_ERROR_INVALID_PARAM);
            }
            $orm->create('core\Task', [
                'name'          => $name,
                'moment'        => $moment,
                'controller'    => $controller,
                'params'        => $params,
                'is_recurring'  => $recurring
            ]);
        }
        catch(\Exception $e) {
            trigger_error("invalid JSON received '$params'", E_USER_WARNING);
        }
    }

    /**
     * Cancel (delete) a scheduled task.
     * #memo - Scheduler always operates as root user.
     * 
     * @param   string    $name         Name of the task to cancel.
     */
    public function cancel($name) {
        $orm = $this->container->get('orm');
        $tasks_ids = $orm->search('core\Task', ['name', '=', $name]);
        if($tasks_ids > 0) {
            $orm->remove('core\Task', $tasks_ids, true);
        }
    }

}