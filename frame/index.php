<?php

class index extends guest {

    function run() {
        Header("Location:https://".UCURL.'/register');
        if(isset($_SESSION['userID']) && $_SESSION['userID'] != ""){
            $user = new user();
            $userInfo = $user->getFulluserInfo($_SESSION['userID']);
            if($userInfo){
                Header("Location:https://".UCURL);
            }
        }
        $this->head = '';
        $this->foot = '';
        $register = isset($this->options['register'])? $this->options['register']: 0;
        if ($register == 0){
            $tab_1 = "active in";
            $tab_2 = "";
        }else{
            $tab_2 = "active in";
            $tab_1 = "";
        }
        $info ="";
//         dump($this->options);die;

		if(isset($_SESSION['red_code'])){
			$this->options['code'] = $_SESSION['red_code'];
		}
        if(isset($this->options['code']) && F::fmtNum($this->options['code'])){
            $db = new MySql();
            $data = $db->getRow("select u_name,u_tel from t_user where u_code = '".$this->options['code']."'");
            if ($data){
                if($data['u_tel']){
                    $phone = F::hidtel($data['u_tel']);
                    $SESSION['referrer'] = $this->options['code'];
                    if ($data['u_name']){
                        $info = "姓名:".$data['u_name']."         联系方式:".$phone;
                    }else{
                        $info = "         联系方式:".$phone;
                    }
                }
            }
        }else{
            if(isset($this->options['rcm_id'])){
                $db = new MySql();
                $data = $db->getRow("select u_name,u_tel,u_code from t_user where u_nick = '".$this->options['rcm_id']."'");
                if ($data){
                    if($data['u_tel']){
                        $phone = F::hidtel($data['u_tel']);
                        $SESSION['referrer'] = $data['u_code'];
                        if ($data['u_name']){
                            $info = "姓名:".$data['u_name']."         联系方式:".$phone;
                        }else{
                            $info = "         联系方式:".$phone;
                        }
                    }
                }
            }
        }
        
        $replaceArray = array(
            'referrer' => (isset($SESSION['referrer'])) ? $SESSION['referrer'] : "",
            'readonly' => (isset($SESSION['referrer'])) ? " readonly" : "",
			'sms_sendInterval' => SMS_SENDINTERVAL,
            'info'  => $info,
            'tab_1' => $tab_1,
            'tab_2' => $tab_2,
			'helpurl' => 'https://'.WWWURL.'/help',
			'abouturl' => 'https://'.WWWURL.'/aboutUs',
			'openurl' => 'https://'.OPENURL,
			'srvurl' => 'https://'.SRVURL,
			'mallurl' => 'https://www.999qf.cn',
			'bbsurl' => 'https://'.BBSURL,
		    //'forget'  => $forget,   
		    'webUrl'  =>'https://'.WWWURL,
		    'help'    =>'https://'.WWWURL.'/help',
		);
//         dump($replaceArray);die;
        $this->setReplaceData($replaceArray);
        $this->setTempAndData();
        $this->show();
    }

}
