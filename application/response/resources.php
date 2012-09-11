<?php

/* Sascha Watermeyer - WTRSAS001
 * Vulamobi CS Honours project
 * sascha.watermeyer@gmail.com */

include_once 'simple_html_dom.php';

class Resources extends CI_Controller 
{
    public function __construct() 
    {
        parent::__construct();
    }
    
    public function Resources() 
    {
        //show_404();
    }
    
    //get roster for a Course
    public function site($site_id)
    {
        if($this->session->userdata('logged_in'))
        {
            //globals
            $tool_id = "";
            $exists = false;
            $resouces = array();
            
            $cookie = $this->session->userdata('cookie');
            $cookiepath = realpath($cookie);
            
            //check "gradebook" in supported tools for site
            $sup_tools = $this->sup_tools($site_id, 0);
            foreach ($sup_tools as $tool) 
            {
                if(array_key_exists('resources',$tool))
                {
                    $exists = true;
                    $tool_id = $tool['tool_id'];
                }
            }
            
            if($exists)
            {
                $url = "https://vula.uct.ac.za/portal/site/" . $site_id . "/page/" . $tool_id;

                //eat cookie..yum
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiepath);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

                $response = curl_exec($curl);

                //create html dom object
                $html_str = str_get_html($response);
                $html = new simple_html_dom($html_str);
                
                if (($iframe_url = $html->find('iframe', 0)->src) != null) 
                {
                    //echo "iframe_url: " . $iframe_url . "</br>";

                    curl_setopt($curl, CURLOPT_URL, $iframe_url);
                    $result = curl_exec($curl);
                    curl_close($curl);

                    $html = str_get_html($result);
                    
                    foreach($html->find('td[headers=title]') as $element)
                    {
                        $innerhtml = str_get_html($element);
                        
                        if($innerhtml->find('a',1)->href == "#")//folder
                        {
                            $valuearray = explode("'",$innerhtml->find('a',1)->onclick);
                            $link = "https://vula.uct.ac.za/access/content" . $valuearray[7];
                            
                            $folder_info = array('text' => $innerhtml->find('a',1)->plaintext
                                                ,'url' => $link
                                                ,'onclick' => 'folderSelected($(this).attr(\'id\'))');
                            $folder = array('folder' => $folder_info);
                            $resouces[] = $folder;
                        }
                        else//resource
                        {
                            $resource_info = array('text' => $innerhtml->find('a',1)->plaintext
                                                   ,'id' => $innerhtml->find('a',1)->href
                                                   ,'onclick' => 'resourceSelected($(this).attr(\'id\'))');
                            $resource = array('resource' => $resource_info);
                            $resouces[] = $resource;
                        }
                    }
                }
            }
            else//doesn't exist
            {
                show_404();
            }
        }
        else//NOT logged in
        {
            $this->session->sess_destroy();
            echo 'logged_out';
        }
    }
    
    
    //returns array of supported tools for a site
    // - name e.g. "CS Honours"
    // - id e.g. "fa532f3e-a2e1-48ec-9d78-3d5722e8b60d"
    //set "$json = 1" if want JSON resposnse else "0"
    public function sup_tools($site_id ,$json)
    {
        if($this->session->userdata('logged_in'))
        {
            $cookie = $this->session->userdata('cookie');
            $cookiepath = realpath($cookie);

            $url = "https://vula.uct.ac.za/portal/site/" . $site_id;

            //eat cookie..yum
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiepath);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            $response = curl_exec($curl);
            curl_close($curl);
            
            //create html dom object
            $html_str = "";
            $html_str = str_get_html($response);
            $html = new simple_html_dom($html_str);
            
            //scrap tools list
            $tools = array();
            $tools_ul = $html->find('#toolMenu', 0);
            $ul = $tools_ul->children(0);
            foreach ($ul->find('li') as $li) 
            {
                foreach ($li->find('a') as $a)
                {
                    $tools[] = $a;
                }
            }
            
            //Check for supported tools
            $sup_tools = array();
            foreach ($tools as $a) 
            {
                switch ($a->class) 
                {
                    case 'icon-sakai-announcements'://announcements
                        $temp_replace = "https://vula.uct.ac.za/portal/site/" . $site_id . "/page/";
                        $tool_id = str_replace($temp_replace, "", $a->href);

                        $tool = array('announcements' => 'announcements'
                                      ,'tool_id' => $tool_id);
                        $sup_tools[] = $tool;
                        break;
                    case 'icon-sakai-chat'://chatroom
                        $temp_replace = "https://vula.uct.ac.za/portal/site/" . $site_id . "/page/";
                        $tool_id = str_replace($temp_replace, "", $a->href);

                        $tool = array('chatroom' => 'chatroom'
                                      ,'tool_id' => $tool_id);
                        $sup_tools[] = $tool;
                        break;
                    case 'icon-sakai-gradebook-tool'://gradebook
                        $temp_replace = "https://vula.uct.ac.za/portal/site/" . $site_id . "/page/";
                        $tool_id = str_replace($temp_replace, "", $a->href);

                        $tool = array('gradebook' => 'gradebook'
                                      ,'tool_id' => $tool_id);
                        $sup_tools[] = $tool;
                        break;
                    case 'icon-sakai-site-roster'://participants
                        $temp_replace = "https://vula.uct.ac.za/portal/site/" . $site_id . "/page/";
                        $tool_id = str_replace($temp_replace, "", $a->href);

                        $tool = array('participants' => 'participants'
                                      ,'tool_id' => $tool_id);
                        $sup_tools[] = $tool;
                        break;
                    case 'icon-sakai-resources'://resources
                        $temp_replace = "https://vula.uct.ac.za/portal/site/" . $site_id . "/page/";
                        $tool_id = str_replace($temp_replace, "", $a->href);

                        $tool = array('resources' => 'resources'
                                      ,'tool_id' => $tool_id);
                        $sup_tools[] = $tool;
                        break;
                    default:
                        break;
                }
            }
            
            //JSON response check
            if($json == 0)//output PHP
            {
                return $sup_tools;
            }
            else if($json == 1)//output JSON
            {
                echo json_encode($sup_tools);
            }
            else
            {
                show_404();
            }
        }
        else//NOT logged in
        {
            $this->session->sess_destroy();
            echo 'logged_out';
        }
    }
}
?>
