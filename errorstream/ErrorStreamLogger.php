<?php

/**
 * ErrorStreamLogger sends error reports from Yii to ErrorStream.com accounts.
 *
 * Installation:
 * install into protected/extensions/errorstream/ErrorStreamLogger.php
 *
 * Example Config:
 *
array(
'class' => 'application.extensions.errorstream.ErrorStreamLogger',
'api_token' => 'YOUR API TOKEN HERE',
'project_token' => 'YOUR PROJECT TOKEN HERE',
'levels'=>'info, error, warning',
),
 */
class ErrorStreamLogger extends CLogRoute
{
    public $url = 'http://www.errorstream.com/api/1.0/errors/create';
    public $api_token = '';
    public $project_token = '';
    public $levels = '';

    /**
     * Init. Yii has two forms of errors.. errors and exceptions.
     * Each one needs to be handled differently.
     */
    public function init()
    {
        Yii::app()->attachEventHandler('onException', array($this, 'handleException'));
        Yii::app()->attachEventHandler('onError', array($this, 'handleError'));
    }

    /**
     * Catch and handle exceptions to errorstream
     */
    public function handleException($event) {
        $this->reportException($event->exception);
    }

    /**
     * Catch and handle errors to errorstream.
     */
    public function handleError($event) {
        if(!is_object($event)){
            return false;
        }

        if(!isset($event->message)){
            return false;
        }

        if(!isset($event->file)){
            return false;
        }

        if(!isset($event->code)){
            return false;
        }

        $report = [
            'error_group'   => $event->message,
            'line_number'   => $event->file,
            'file_name'     => $event->file,
            'message'       => $event->message,
            'stack_trace'   => $event->code,
            'severity'      => 3,
        ];

        $this->sendReport($report);

    }

    /**
     * Logging function that hooks into Yii's platform. Catches logging messages and sends them to the service.
     * @param array $logs
     * @return bool
     */
    protected function processLogs($logs)
    {
        //Don't send anything when in debug mode.
        if (defined('YII_DEBUG') && YII_DEBUG === true) {
            return false;
        }

        //Process logs
        foreach($logs as $log) {

            $array = explode("\n", $log[0]);
            $message = implode('<br>', $array);

            $report = [
                'error_group'   => $message,
                'line_number'   => 0,
                'file_name'     => 'N/A',
                'message'       => $message,
                'severity'      => $this->getSeverity($log),
            ];

            $this->makeRequest($report);
        }
    }

    /**
     * Translates yii logging into errorstream logging levels
     * @param $log
     * @return int
     */
    protected function getSeverity($log){
        $severity = 3;
        if($log[1] == 'trace') $severity = 1;
        if($log[1] == 'info') $severity = 1;
        if($log[1] == 'profile') $severity = 2;
        if($log[1] == 'warning') $severity = 2;
        if($log[1] == 'error') $severity = 3;
        return $severity;
    }

    /**
     * Reports on yii exceptions. ignores 404 errors.
     * @param Exception $ex
     * @return bool
     */
    public function reportException(Exception $ex)
    {
        //Dont do anything in debug mode.
        if (defined('YII_DEBUG') && YII_DEBUG === true) {
            return false;
        }

        //Ignore 404 errors.
        if(isset($ex->statusCode) && $ex->statusCode == '404'){
            return false;
        }

        $array = explode("\n", $ex->getTraceAsString());
        $trace = implode('<br>', $array);

        $report = new stdClass();
        $report->error_group = $ex->getMessage().':'.$ex->getLine();
        $report->line_number = $ex->getLine();
        $report->file_name = $ex->getFile();
        $report->message = $ex->getMessage();
        $report->stack_trace = $trace;
        $report->severity = 3;
        $this->makeRequest($report);
    }

    /**
     * Sends requests to errorstream services
     * @param $data
     */
    private function makeRequest($data)
    {
        $authData = ['api_token' => $this->api_token, 'project_token' => $this->project_token];
        $url = $this->url . '?' . http_build_query($authData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($data)))
        );
        curl_exec($ch);
    }
}