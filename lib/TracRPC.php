<?php
namespace TracRPC;

/**
 * Trac Remote Procedure Call (RPC) Class
 *
 * The purpose of this class is to interact with the Trac API from a remote location by remote procedure calls.
 *
 * Trac is a project management and bug/issue tracking system.
 * @link http://trac.edgewall.org/
 *
 * Trac by itself does not provide an API. Therefor the XmlRpcPlugin has to be installed.
 * Trac then provides anonymous and authenticated access to the API via two protocols XML-RPC and JSON-RPC.
 * @http://trac-hacks.org/wiki/XmlRpcPlugin/
 */
class TracRPC
{

    public $tracURL = '';
    public $username = '';
    public $password = '';
    public $multiCall = false;
    public $json_decode = true;
    public $error = '';
    private $request = false;
    private $response = false;
    private $request_id = 0;

    /**
     * Construtor for TracRPC
     *
     * @param	string	The complete url. Example: https://example.org/login/xmlrpc
     * @param	array	Name/Value paired array to set properties.
     */
    public function __construct($tracURL = '', $params = array())
    {
        // CURL extension is required
        if (function_exists('curl_init') === false) {
            throw new \BadFunctionCallException('CURL extension disabled. Please enable it in "php.ini".');
        }

        $this->tracURL = $tracURL;

        if ((array) $params === $params and count($params) > 0) {
            $this->username = isset($params['username']) ? $params['username'] : '';
            $this->password = isset($params['password']) ? $params['password'] : '';
            $this->multiCall = isset($params['multiCall']) ? (bool) $params['multiCall'] : false;
            $this->json_decode = isset($params['json_decode']) ? (bool) $params['json_decode'] : true;
        }
    }

    /**
     * Get the recent changed wiki pages.
     *
     * Trac API -> wiki.getRecentChanges()
     *
     * @param	int		A timestamp integer. Defaults to current day.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getRecentChangedWikiPages($date = 0)
    {
        if ($date == false) {
            $date = array('datetime', date("o-m-d\T00:00:00"));
        } elseif (is_numeric($date) === true) {
            $date = array('datetime', date("o-m-d\TH:i:s+00:00", $date));
        }

        $this->addRequest('wiki.getRecentChanges', array(array('__jsonclass__' => $date)));

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }
        if ($this->multiCall === true) {
            return $this->request_id;
        }

        return false;
    }

    /**
     * Get a wiki page in it's RAW format or HTML. Can get a version of a page.
     *
     * @param	string	Wiki page name.
     * @param	int		Version of the page to get.
     * @param	bool	true gets raw wiki page. false will return HTML.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getWikiPage($name = '', $version = 0, $raw = true)
    {
        if ($name == '') {
            return false;
        }

        if ($version == 0) {
            if ($raw === true) {
                $this->addRequest('wiki.getPage', array($name));
            } else {
                $this->addRequest('wiki.getPageHTML', array($name));
            }
        } else {
            if ($raw === true) {
                $this->addRequest('wiki.getPageVersion', array($name, $version));
            } else {
                $this->addRequest('wiki.getPageHTMLVersion', array($name, $version));
            }
        }

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get page info for a specific wiki page and possible version of the page.
     *
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getWikiPageInfo($name = '', $version = 0)
    {
        if ($name == '') {
            return false;
        }

        if ($version == 0) {
            $this->addRequest('wiki.getPageInfo', array($name));
        } else {
            $this->addRequest('wiki.getPageInfoVersion', array($name, $version));
        }

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get a list of wiki pages in TRAC.
     *
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getWikiPages()
    {
        $this->addRequest('wiki.getAllPages');

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get the recent changed tickets.
     *
     * @param	int		A timestamp integer. Defaults to current day.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getRecentChangedTickets($date = 0)
    {
        if ($date == false) {
            $date = array('datetime', date("o-m-d\T00:00:00"));
        } elseif (is_numeric($date) === true) {
            $date = array('datetime', date("o-m-d\TH:i:s+00:00", $date));
        }

        $this->addRequest('ticket.getRecentChanges', array(array('__jsonclass__' => $date)));

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get a ticket.
     *
     * @param	string	The id of the ticket.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicket($id = '')
    {
        if ($id == '') {
            return false;
        }

        $this->addRequest('ticket.get', $id);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get a all ticket fields.
     *
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketFields()
    {
        $this->addRequest('ticket.getTicketFields');

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get the recent changed tickets.
     *
     * @param	string	The id of the ticket.
     * @param	int		When in the changelog.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketChangelog($id = '', $when = 0)
    {
        if ($id == '') {
            return false;
        }

        $this->addRequest('ticket.changeLog', array($id, $when));

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get a ticket actions.
     *
     * @param	string	The id of the ticket.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketActions($id = '')
    {
        if ($id == '') {
            return false;
        }

        $this->addRequest('ticket.getActions', $id);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Perform requests relating to attachments for wiki pages.
     *
     * Get a list of attachments for a wiki page.
     * Get an attachment for a wiki page.
     * Delete an attachment for a wiki page.
     * Creat an attachment for a wiki page.
     *
     * @param	string	What action to perform for ticket attachments.
     * 					Possible values list, get, delete, or create.
     * 					Default list.
     * @param	string	The pagename of the wiki page.
     * @param	string	Filenamepath of the file to add to the wiki page.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getWikiAttachments($action = 'list', $name = '', $file = '')
    {
        if ($name == '') {
            return false;
        }

        $method = '';
        $params = array($name);

        switch ($action) {
            case 'list':
            default:
                $method = 'wiki.listAttachments';
                break;
            case 'get':
                if ($file == '') {
                    return false;
                }

                $method = 'wiki.getAttachment';
                $params[] = $file;
                break;
            case 'delete':
                if ($file == '') {
                    return false;
                }

                $method = 'wiki.deleteAttachment';
                $params[] = $file;
                break;
            case 'create':
                if (is_file($file) === false) {
                    return false;
                }

                $contents = file_get_contents($file, FILE_BINARY);

                if ($contents === true) {
                    $contents = array('__jsonclass__' => array('binary', base64_encode($contents)));
                }

                $method = 'wiki.putAttachment';
                $params[] = basename($file);
                $params[] = $contents;
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Perform requests relating to attachments for tickets.
     *
     * Get a list of attachments for a ticket.
     * Get an attachment for a ticket.
     * Delete an attachment for a ticket.
     * Creat an attachment for a ticket.
     *
     * @param	string	What action to perform for ticket attachments.
     * 					Possible values list, get, delete, or create.
     * 					Default list.
     * @param	string	The id of the ticket.
     * @param	string	Filenamepath of the file to add to the ticket.
     * @param	string	Description of the attachment.
     * @param	bool	true will replace the attachment if it exists false will not replace it.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketAttachments($action = 'list', $id = '', $file = '', $desc = '', $replace = true)
    {
        if ($id == '') {
            return false;
        }

        $method = '';
        $params = array($id);

        switch ($action) {
            case 'list':
            default:
                $method = 'ticket.listAttachments';
                break;
            case 'get':
                if ($file == '') {
                    return false;
                }

                $method = 'ticket.getAttachment';
                $params[] = $file;
                break;
            case 'delete':
                if ($file == '') {
                    return false;
                }

                $method = 'ticket.deleteAttachment';
                $params[] = $file;
                break;
            case 'create':
                if (is_file($file) === false) {
                    return false;
                }

                $contents = file_get_contents($file, FILE_BINARY);

                if ($contents === true) {
                    $contents = array('__jsonclass__' => array('binary', base64_encode($contents)));
                }

                $method = 'ticket.putAttachment';
                $params[] = basename($file);
                $params[] = $desc;
                $params[] = $contents;
                $params[] = $replace;
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Create or delete a wiki page.
     *
     * @param	string	What action to perform for a ticket.
     * 					Possible values create or delete.
     * 					Default create.
     * @param	string	The pagename of the wiki page.
     * @param	string	The content of the wiki page to set.
     * @param	array	Name/value paired array of data for the wiki page.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getWikiUpdate($action = 'create', $name = '', $page = '', $data = array())
    {
        if ($name == '') {
            return false;
        }

        $method = '';
        $params = array();

        switch ($action) {
            case 'create':
            default:
                $method = 'wiki.putPage';
                $params = array(
                                                            0 => $name,
                                                            1 => $page,
                                                            2 => $data
                );
                break;
            case 'delete':
                $method = 'wiki.deletePage';
                $params = $name;
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Create, delete, or update a ticket.
     *
     * @param	string	What action to perform for a ticket.
     * 					Possible values create, update, or delete.
     * 					Default create.
     * @param	string	The id of the ticket.
     * @param	array	Name/value paired array of data for the ticket.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketUpdate($action = 'create', $id = '', $data = array())
    {
        $method = '';
        $params = array();

        switch ($action) {
            case 'create':
            default:
                $method = 'ticket.create';
                $params = array(
                    0 => ($data['summary'] !== null) ? $data['summary'] : '',
                    1 => ($data['desc'] !== null) ? $data['desc'] : '',
                    2 => ($data['attr'] !== null) ? $data['attr'] : array(),
                    3 => ($data['notify'] !== null) ? $data['notify'] : false
                );
                break;
            case 'update':
                $method = 'ticket.update';
                $params = array(
                    0 => $id,
                    1 => ($data['comment'] !== null) ? $data['comment'] : '',
                    2 => ($data['attr'] !== null) ? $data['attr'] : array(),
                    3 => ($data['notify'] !== null) ? $data['notify'] : false
                );
                break;
            case 'delete':
                if ($id == '') {
                    return false;
                }

                $method = 'ticket.delete';
                $params = $id;
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Search for tickets.
     *
     * @param	string	Query string to search.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketSearch($query = '')
    {
        if (is_array($query) === true and false === empty($query)) {
            $ops = array('=', '~=', '^=', '$=', '!=', '!~=', '!^=', '!$=');
            $query_str = '';
            foreach ($query as $key => $value) {
                if (is_array($value) === true) {
                    $value = implode('|', $value);
                    $query[$key] = $value;
                }

                if (false === empty($value)) {
                    $op = '=';

                    foreach ($ops as $sign) {
                        if (strrpos($sign, $key) === true) {
                            $op = '';
                            break;
                        }
                    }

                    $query_str .= $key . $op . $value . '&';
                }
            }

            if (empty($query_str)) {
                return false;
            }

            $query = substr($query_str, 0, -1);
            unset($query_str);
        } elseif (empty($query)) {
            return false;
        }

        $this->addRequest('ticket.query', $query);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get all ticket components, get a specific component,
     * create a component, edit an existing component, or delete a component.
     *
     * @param	string	What action to perform for ticket component.
     * 					Possible values get_all, get, delete, update, or create.
     * 					Default get_all.
     * @param	string	The name of the component.
     * @param	array	Name/value paired array of data for the ticket component.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketComponent($action = 'get_all', $name = '', $attr = array())
    {
        $method = '';
        $params = '';

        switch ($action) {
            case 'get_all':
            default:
                $method = 'ticket.component.getAll';
                break;
            case 'get':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.component.get';
                $params = array(0 => $name);

                break;
            case 'delete':
                if ($name == false) {
                    return false;
                }

                $method = 'ticket.component.delete';
                $params = array(0 => $name);
                break;
            case 'update':
            case 'create':
                if ($name == '' or !is_array($attr)) {
                    return false;
                }

                $method = 'ticket.component.' . $action;
                $params = array($name, $attrs);
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get all ticket milestones, get a specific milestone,
     * create a milestone, edit an existing milestone, or delete a milestone.
     *
     * @param	string	What action to perform for ticket milestone.
     * 					Possible values get_all, get, delete, update, or create.
     * 					Default get_all.
     * @param	string	The name of the milestone.
     * @param	array	Name/value paired array of data for the ticket milestone.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketMilestone($action = 'get_all', $name = '', $attr = array())
    {
        $method = '';
        $params = '';

        switch ($action) {
            case 'get_all':
            default:
                $method = 'ticket.milestone.getAll';
                break;
            case 'get':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.milestone.get';
                $params = array(0 => $name);

                break;
            case 'delete':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.milestone.delete';
                $params = array(0 => $name);
                break;
            case 'update':
            case 'create':
                if ($name == '' or !is_array($attr)) {
                    return false;
                }

                $method = 'ticket.milestone.' . $action;
                $params = array($name, $attr);
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get all ticket prioritys, get a specific priority,
     * create a priority, edit an existing priority, or delete a priority.
     *
     * @param	string	What action to perform for ticket priority.
     * 					Possible values get_all, get, delete, update, or create.
     * 					Default get_all.
     * @param	string	The name of the priority.
     * @param	string	Priority name.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketPriority($action = 'get_all', $name = '', $attr = '')
    {
        $method = '';
        $params = '';

        switch ($action) {
            case 'get_all':
            default:
                $method = 'ticket.priority.getAll';
                break;
            case 'get':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.priority.get';
                $params = array(0 => $name);

                break;
            case 'delete':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.priority.delete';
                $params = array(0 => $name);
                break;
            case 'update':
            case 'create':
                if ($name == '' or $attr == '') {
                    return false;
                }

                $method = 'ticket.priority.' . $action;
                $params = array($name, $attr);
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get all ticket resolutions, get a specific resolution,
     * create a resolution, edit an existing resolution, or delete a resolution.
     *
     * @param	string	What action to perform for ticket resolution.
     * 					Possible values get_all, get, delete, update, or create.
     * 					Default get_all.
     * @param	string	The name of the resolution.
     * @param	string	Resolution name.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketResolution($action = 'get_all', $name = '', $attr = '')
    {
        $method = '';
        $params = '';

        switch ($action) {
            case 'get_all':
            default:
                $method = 'ticket.resolution.getAll';
                break;
            case 'get':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.resolution.get';
                $params = array(0 => $name);

                break;
            case 'delete':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.resolution.delete';
                $params = array(0 => $name);
                break;
            case 'update':
            case 'create':
                if ($name == '' or $attr == '') {
                    return false;
                }

                $method = 'ticket.resolution.' . $action;
                $params = array($name, $attr);
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get all ticket severitys, get a specific severity,
     * create a severity, edit an existing severity, or delete a severity.
     *
     * @param	string	What action to perform for ticket severity.
     * 					Possible values get_all, get, delete, update, or create.
     * 					Default get_all.
     * @param	string	The name of the severity.
     * @param	string	Severity name.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketSeverity($action = 'get_all', $name = '', $attr = '')
    {
        $method = '';
        $params = '';

        switch ($action) {
            case 'get_all':
            default:
                $method = 'ticket.severity.getAll';
                break;
            case 'get':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.severity.get';
                $params = array(0 => $name);

                break;
            case 'delete':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.severity.delete';
                $params = array(0 => $name);
                break;
            case 'update':
            case 'create':
                if ($name == '' or $attr == '') {
                    return false;
                }

                $method = 'ticket.severity.' . $action;
                $params = array($name, $attr);
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get all ticket types, get a specific type, create a type, edit an existing type, or delete a type
     *
     * @param	string	What action to perform for ticket type.
     * 					Possible values get_all, get, delete, update, or create.
     * 					Default get_all.
     * @param	string	The name of the type.
     * @param	string	Type name.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketType($action = 'get_all', $name = '', $attr = '')
    {
        $method = '';
        $params = '';

        switch ($action) {
            case 'get_all':
            default:
                $method = 'ticket.type.getAll';
                break;
            case 'get':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.type.get';
                $params = array(0 => $name);

                break;
            case 'delete':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.type.delete';
                $params = array(0 => $name);
                break;
            case 'update':
            case 'create':
                if ($name == '' or $attr == '') {
                    return false;
                }

                $method = 'ticket.type.' . $action;
                $params = array($name, $attr);
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get all ticket versions, get a specific version,
     * create a version, edit an existing version, or delete a version.
     *
     * @param	string	What action to perform for ticket version.
     * 					Possible values get_all, get, delete, update, or create.
     * 					Default get_all.
     * @param	string	The name of the version.
     * @param	array	Name/value paired array of data for the ticket version.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketVersion($action = 'get_all', $name = '', $attr = array())
    {
        $method = '';
        $params = array();

        switch ($action) {
            case 'get_all':
            default:
                $method = 'ticket.version.getAll';
                break;
            case 'get':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.version.get';
                $params = array(0 => $name);

                break;
            case 'delete':
                if ($name == '') {
                    return false;
                }

                $method = 'ticket.version.delete';
                $params = array(0 => $name);
                break;
            case 'update':
            case 'create':
                if ($name == '' or !is_array($attr)) {
                    return false;
                }

                $this->set_method('ticket.version.' . $action);
                break;
        }

        $this->addRequest($method, $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get all status.
     *
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getTicketStatus()
    {
        $this->addRequest('ticket.status.getAll');

        if ($this->doRequest() === true) {
            return $this->getResponse();
        } elseif ($this->multiCall === true) {
            return $this->request_id;
        }

        return false;
    }

    /**
     * Perform a global search in TRAC.
     *
     * @param	string	Query string to search for,
     * @param	array	Search filters to use.
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getSearch($query = '', $filter = array())
    {
        $params = array();

        if ($query != '') {
            $params[0] = $query;
        } else {
            return false;
        }

        if (is_array($filter) === true and false === empty($filter)) {
            $params[1] = $filter;
        }

        $this->addRequest('search.getSearchFilters', $params);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Convert a string of raw wiki text to HTML.
     *
     * @param	string	A string of raw wiki text.
     * @return mixed The result of the requet or the integer id on a multi_call. false on error.
     */
    public function getWikiTextToHTML($text = '')
    {
        if ($text == '') {
            return false;
        }

        $this->addRequest('wiki.wikiToHTML', $text);

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get all search filter
     *
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getSearchFilters()
    {
        $this->addRequest('search.getSearchFilters');

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Get the API version from Trac.
     *
     * @return mixed The result of the requet or the integer id on a muli_call. false on error.
     */
    public function getApiVersion()
    {
        $this->addRequest('system.getAPIVersion');

        if ($this->multiCall === true) {
            return $this->request_id;
        }

        if ($this->doRequest() === true) {
            return $this->getResponse();
        }

        return false;
    }

    /**
     * Executes a RPC request to Trac. Accepts method and arguments.
     *
     * @param	string	A RPC method to execute.
     * @param	array	Arguments to pass with the RPC call.
     * @return bool true on a successful request. false on error.
     */
    public function doRequest($method = '', $args = array())
    {
        if ($method != '') {
            $this->addRequest($method, $args);
        }

        if (empty($this->request)) {
            return false;
        }

        if ($this->multiCall === true) {
            $this->addRequest('system.multicall');
        }

        // json_encode $this->_request
        if (is_array($this->request) === true) {
            $this->request = json_encode(array_pop($this->request));

            $this->request = str_replace(':', ': ', $this->request);
            $this->request = str_replace(',', ', ', $this->request);
        }

        if ($this->doCurlRequest() === true) {
            $this->parseResult();

            return true;
        } else {
            return false;
        }
    }

    /**
     * Adds a new request to the request stack
     *
     * @param	string	The method name to call.
     * @param	array	Arguments to pass with the call.
     * @param	string	The id to set to the call.
     * @return bool Always true.
     */
    private function addRequest($method = '', $args = array(), $id = '')
    {
        if ($method == '') {
            return false;
        }

        if (false === is_array($args) and false === empty($args)) {
            $args = array($args);
        } elseif (false === is_array($args)) {
            $args = array();
        }

        if (false === is_array($this->request)) {
            $this->request = array();
        }

        if (empty($id)) {
            $id = $this->incrementRequestId();
        }

        if ($method == 'system.multicall') {
            $request = array(
                'method' => $method,
                'params' => $this->request,
                'id' => $id
            );

            $this->request = array(0 => $request);
        } else {
            $this->request[] = array(
                'method' => $method,
                'params' => $args,
                'id' => $id
            );
        }

        return true;
    }

    /**
     * Increment the current payload id by 1 and returns it.
     *
     * @return int The incremented request id.
     */
    private function incrementRequestId()
    {
        return $this->request_id + 1;
    }

    /**
     * Make the request using CURL.
     *
     * @return bool true is a successful CURL request. false CURL isn't installed or the url or payload is empty.
     */
    private function doCurlRequest()
    {
        if (empty($this->tracURL)) {
            exit('Provide the URL to the Trac Env you want to query.');
        }

        if (empty($this->request)) {
            exit('No valid Request.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->tracURL);

        /**
         * Set correct HttpHeader Content Type
         * depending on the requested content type via tracURL.
         *
         * The JSON-RPC protocol gets used when sending
         * a 'Content-Type': 'application/json' header request to:
         *
         * Anonymous access
         *
         *      http://trac.example.com/rpc
         *      http://trac.example.com/jsonrpc
         *
         * Authenticated access
         *
         *      http://trac.example.com/login/rpc
         *      http://trac.example.com/login/jsonrpc
         *
         * The XML-RPC protocol gets used when sending
         * a 'Content-Type': 'application/xml' or 'text/xml' header request to:
         *
         * Anonymous access
         *
         *      http://trac.example.com/rpc
         *      http://trac.example.com/xmlrpc
         *
         * Authenticated access
         *
         *      http://trac.example.com/login/rpc
         *      http://trac.example.com/login/xmlrpc
         */
        if (strpos($this->tracURL, 'jsonrpc') !== false) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        }

        if (strpos($this->tracURL, 'xmlrpc') !== false) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // workaround for CURLOPT_FOLLOWLOCATION
        // cannot be activated when safe_mode is enabled or an open_basedir is set
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        } else {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            // alternative following handling via header 301?
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request);

        /**
         * Determine if this is an authenticated access, then set user credentials accordingly.
         */
        if (strpos($this->tracURL, 'login') !== false) {
            if (empty($this->username) or empty($this->password)) {
                throw new \Exception(
                    'You are trying an authenticated access without providing username and password.'
                );
            } else {
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY); // CURLAUTH_BASIC | CURLAUTH_DIGEST
                curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            }
        }

        $response = trim(curl_exec($ch));

        if (curl_errno($ch) > 0) {
            $this->error = curl_error($ch);
        }

        curl_close($ch);

        if ($this->json_decode === true) {
            $this->response = json_decode($response);
        } else {
            $this->response = $response;
        }

        return true;
    }

    /**
     * Loop through the results and do any parsing needed.
     *
     * JSON RPC doesn't have datatypes so special objects are made for datetime
     * and base64 value. This method finds those objects and converts them into
     * proper php values. For datetime types, the value is converted into a UNIX
     * timestamp. Base64 decodes the value.
     *
     * @return bool true on a non-empty result and false if it is empty.
     */
    public function parseResult($response = array())
    {
        if (empty($response)) {
            $response = $this->getResponse();

            $this->response = array();
        }

        if (false === is_object($response) and false === is_array($response)) {
            return false;
        }

        if (($response->result !== null) and is_array($response->result)) {
            foreach ($response->result as $key => $resp) {
                if (isset($resp->result) === true) {
                    $this->parseResult($resp);
                    continue;
                }

                if (is_array($resp) === true or is_object($resp) === true) {
                    $values = array();
                    foreach ($resp as $r_key => $value) {
                        if ($r_key === '__jsonclass__') {
                            switch ($value[0]) {
                                case 'datetime':
                                    $value = strtotime($value[1]);
                                    break;
                                case 'binary':
                                    $value = base64_decode($value[1]);
                                    break;
                            }

                            $values = $value;
                        } else {
                            $values[$r_key] = $value;
                        }
                    }

                    $response->result[$key] = $values;
                } else {
                    $response->result[$key] = $resp;
                }
            }
        }

        $id = 0;
        if (($response->id !== null) and ($response->id != null)) {
            $id = $response->id;
        }

        $this->response[$id] = $response->result;
        $this->error[$id] = false;

        if (($response->error !== null) and is_object($response->error) === true) {
            foreach ($response->error as $key => $value) {
                $this->error[$id][$key] = $value;
            }
        }

        return true;
    }

    /**
     * Set the property user.
     *
     * @return object TracRPC
     */
    public function setUser($username = '')
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the property password.
     *
     * @return object TracRPC
     */
    public function setPassword($password = '')
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set the property tracURL
     *
     * @return object TracRPC
     */
    public function setTracURL($tracURL = '')
    {
        $this->tracURL = $tracURL;

        return $this;
    }

    /**
     * Set the property multiCall.
     *
     * @return bool
     */
    public function setMultiCall($multi = false)
    {
        $this->multiCall = ($multi === true) ? true : false;

        return $this->multiCall;
    }

    /**
     * Set the property json_decode.
     *
     * @return object TracRPC
     */
    public function setJsonDecode($json_decode = false)
    {
        $this->json_decode = ($json_decode === true) ? true : false;

        return $this;
    }

    /**
     * Get the response from the request.
     *
     * @param	int		The id of the call.
     * @return object stdClass
     */
    public function getResponse($id = false)
    {
        if (is_object($this->response) === true) {
            return $this->response;
        } elseif (is_array($this->response) === true) {
            if ($id === true) {
                if (false === is_array($id)) {
                    return $this->response[$id];
                } else {
                    $ret = array();

                    foreach ($id as $key) {
                        if (false === isset($this->response[$key])) {
                            continue;
                        }

                        $ret[$key] = $this->response[$key];
                    }

                    return $ret;
                }
            } else {
                return current($this->response);
            }
        } else {
            return false;
        }
    }

    /**
     * Get any error message set for the request.
     *
     * @param	bool	The id of the call made. Used for multiCalls.
     * @return string The error message
     */
    public function getErrorMessage($id = false)
    {
        if ($id === true) {
            if (is_array($id) === true) {
                $ret = array();

                foreach ($id as $eid) {
                    if (isset($this->error[$eid]) === false) {
                        continue;
                    }

                    $ret[$eid] = $this->error[$eid];
                }

                return $ret;
            }

            if ($this->error[$id] !== null) {
                return $this->error[$id];
            }
        }

        return $this->error;
    }
}
