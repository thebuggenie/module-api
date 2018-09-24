<?php

    namespace thebuggenie\modules\api;

    use GuzzleHttp\Client,
        GuzzleHttp\Psr7\Request,
        thebuggenie\core\framework\cli\Command;

    /**
     * CLI remote command class
     *
     * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
     * @version 3.1
     * @license http://opensource.org/licenses/MPL-2.0 Mozilla Public License 2.0 (MPL 2.0)
     * @package thebuggenie
     * @subpackage core
     */

    /**
     * CLI remote command class
     *
     * @package thebuggenie
     * @subpackage core
     */
    abstract class RemoteCommand extends Command
    {

        protected $_current_remote_server = null;

        protected $_current_remote_user = null;

        protected $_current_remote_password_hash = null;

        protected function _initializeUrlFopen()
        {
            if (!ini_get('allow_url_fopen'))
            {
                $this->cliEcho("The php.ini directive ", 'yellow');
                $this->cliEcho("allow_url.fopen", 'yellow', 'bold');
                $this->cliEcho(" is not set to 1\n", 'yellow');
                $this->cliEcho("Trying to set correct value for the current run ...");
                ini_set('allow_url_fopen', 1);
                if (!ini_get('allow_url_fopen'))
                {
                    throw new \Exception('Could not set "allow_url_fopen" to correct value. Please fix your cli configuration.');
                }
                else
                {
                    $this->cliEcho('OK', 'green', 'bold');
                    $this->cliEcho("\n\n");
                }
            }
        }
        
        protected function _setup()
        {
            $this->_initializeUrlFopen();
        }

        protected function _prepare()
        {
            $this->_current_remote_server = file_get_contents(THEBUGGENIE_CONFIG_PATH . '.remote_server');
            $this->_current_remote_user = file_get_contents(THEBUGGENIE_CONFIG_PATH . '.remote_username');
            $this->_current_remote_password_hash = file_get_contents(THEBUGGENIE_CONFIG_PATH . '.remote_token');
        }

        protected function _getCurrentRemoteServer()
        {
            return $this->_current_remote_server;
        }

        protected function _getCurrentRemoteUser()
        {
            return $this->_current_remote_user;
        }

        protected function _getCurrentRemotePasswordHash()
        {
            return $this->_current_remote_password_hash;
        }

        protected function getRemoteResponse($url, $form_params = [])
        {
            $headers = ["Accept-language" => "en"];
            if ($this->getCommandName() != 'authenticate')
            {
                if (!file_exists(THEBUGGENIE_CONFIG_PATH . '.remote_server') ||
                    !file_exists(THEBUGGENIE_CONFIG_PATH . '.remote_username') ||
                    !file_exists(THEBUGGENIE_CONFIG_PATH . '.remote_token'))
                {
                    throw new \Exception("Please specify an installation of The Bug Genie to connect to by running the remote:authenticate command first");
                }
                $headers["Authorization"] = "Bearer {$this->_getCurrentRemoteUser()}.{$this->_getCurrentRemotePasswordHash()}";
            }

            $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => $this->_current_remote_server,
                // You can set any number of default request options.
                'timeout'  => 5.0,
            ]);
            $method = (empty($form_params)) ? 'GET' : 'POST';
            $options = ['headers' => $headers];
            if ($form_params) {
                $options['form_params'] = $form_params;
            }
            $response = $client->request($method, $url, $options);

            if ($response->getStatusCode() == 200) {
                return json_decode($response->getBody());
            } else {
                throw new \Exception($url . " could not be retrieved:\n" . $response->getBody());
            }
        }

        protected function getRemoteURL($route_name, $params = [])
        {
            $url = \thebuggenie\core\framework\Context::getRouting()->generate($route_name, $params, true);
            $host = $this->_getCurrentRemoteServer();
            if (mb_substr($host, mb_strlen($host) - 2) != '/') $host .= '/';

            $final_url = $host . mb_substr($url, 1);

            return $final_url;
        }

    }
