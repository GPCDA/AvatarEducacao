<?php
require_once("../../config.php");

/* avatar config */
require_once ($CFG->dirroot."/blocks/avatar/config.php");

/* avatar model */
require_once ($CFG->dirroot."/blocks/avatar/model_avatar.php");

/* avatar lib */
require_once ($CFG->dirroot."/blocks/avatar/lib/avatar_lib.php");

class avatar_mobile {

    private $DB, $CFG;

    private $model_avatar;
    private $frm;
    private $logged;
    private $userid;
    private $user;
    private $courses;
    private $last_sync;
    private $last_login_moodle;

    function __construct($frm) {
        global $DB, $CFG;
        $this->DB = $DB;
        $this->CFG = $CFG;
        
        $this->model_avatar = new model_avatar();

        $this->frm = $frm;
        $this->logged = false;
        $this->userid = null;
        $this->user = null;
        $this->courses = null;
        $this->last_sync = null;
        $this->last_login_moodle = null;

        $this->mobile_authentication();
        $this->json_generator();
    }

    private function mobile_authentication() {
        //se alguém tiver submetido algo (POST, e não GET)...
        if ($this->frm) {
            //aqui é se o usuário não tiver um token... ele irá pedir um token para poder acessar o json de conteúdo
            if (isset($this->frm->json) && $this->frm->json == "token") {
                //Irá se autenticar, se conseguir, irá gerar um token
                if (isset($this->frm->username)) {
                    $this->frm->username = trim(moodle_strtolower($this->frm->username));
                    $user = authenticate_user_login($this->frm->username, $this->frm->password);
                    
                    if ($user != false || $user != null) {

                        $token_string = md5(uniqid(rand(), true));
                        $expires = $_SERVER['REQUEST_TIME'] + 2629743; //2629743 segundos é aproximadamente 1 mês.

                        $this->model_avatar->save_token_mobile($user->id, $token_string, $expires);

                        /*
                         * Informações para o JSON referente ao token
                         */
                        $json_token = "";
                        $json_token['tk'] = $token_string; //token
                        $json_token['exp'] = $expires; //expired_in
                        echo json_encode($json_token);
                    }
                }
            } else if (isset($this->frm->json) && $this->frm->json == "content" && isset($this->frm->token)) { //o usuário já tem um token e agora deseja pegar o json de conteúdo
                //se o token ainda for válido, o usuário estará finalmente logado para pegar o json de conteúdo
                if ($this->model_avatar->validate_token_mobile($this->frm->token)) {
                    $token_obj = $this->model_avatar->get_token($this->frm->token);

                    $this->logged = true;

                    $this->userid = $token_obj->userid;
                    
                    $this->user = $this->model_avatar->get_user($this->userid);

                    $this->courses = enrol_get_users_courses($this->userid, true);

                    //timestamp referente a última sincronização do usuário
                    $this->last_sync = $this->model_avatar->get_last_sync($this->userid);

                    $this->last_login_moodle = $this->user->currentlogin;
                }
            }
        }
    }

    private function json_generator() {
        if ($this->logged) {

            /*
             * Verificando a mensagem do adm
             */
            $adm_courseid = 1;

            $return_adm_msg = $this->load_files(0, $adm_courseid, 3, 'msg', null, 'mp3', null);

            $adm_avatar = $this->model_avatar->get_course_avatar($adm_courseid);

            $avatarid = $adm_avatar->avatarid;

            if($adm_avatar->gender == "female") {
                $avatarid += 10;
            }

            $count = 0;
            $json = "";
            if($avatarid) {
                $json['content'][$count]['id'] = null;
                $json['content'][$count]['name'] = ""; //nome do curso
                $json['content'][$count]['pf'] = "adm"; //profile
                $json['content'][$count]['avt'] = intval($avatarid); //avatar
                $json['content'][$count]['msgn'] = $return_adm_msg['msgn']; //new?
                $json['content'][$count]['msgu'] = $return_adm_msg['msgu']; //update?
                $json['content'][$count]['msga'] = $return_adm_msg['msga']; //áudio
                $json['content'][$count]['msgv'] = $return_adm_msg['msgv']; //vis
                $json['content'][$count]['notn'] = 0;
                $json['content'][$count]['notu'] = 0;
                $json['content'][$count]['nota'] = "";
                $json['content'][$count]['notv'] = "";
                $count++;
            }

            foreach ($this->courses as $course) {

                /* caso o curso não possua o avatar, não deve ser criado json pra ele */
                if(!$this->model_avatar->exists_course_avatar($course->id)) {
                    continue;
                }

                /*
                 * É preciso verificar os parâmetros da lib_avatar ainda...
                 * Cada curso pode ter um parâmetro diferente...
                 */

                $avatar_course = $this->model_avatar->get_course_avatar($course->id);
                $new_contents_sent = $avatar_course->new_contents_sent;
                $new_activities_sent = $avatar_course->new_activities_sent;
                $pending_activities = $avatar_course->pending_activities;

                $avatar_lib = new avatar_lib($this->userid, $course->id, $new_contents_sent, $new_activities_sent, $pending_activities);

                $gender = $avatar_course->gender;
                $avatarid = $avatar_course->avatarid;

                $speaker = "";
                if ($gender == "male") {
                    $speaker = "cid";
                } else {
                    $speaker = "lis";
                    $avatarid += 10;
                }

                /*
                 * Verificando a mensagem do curso
                 */
                $return_prf_msg = $this->load_files(0, $course->id, 2, 'msg', null, 'mp3', null);

                /*
                 * Verificando a noticação para o aluno, referente ao curso
                 */
                $return_prf_not = $this->load_files($this->userid, $course->id, 1, 'not', $speaker, 'mp3', $avatar_lib);

                $json['content'][$count]['id'] = $course->id;
                $json['content'][$count]['name'] = $course->fullname;
                $json['content'][$count]['pf'] = "prf"; //profile
                $json['content'][$count]['avt'] = intval($avatarid); //avatar
                $json['content'][$count]['msgn'] = $return_prf_msg['msgn']; //new?
                $json['content'][$count]['msgu'] = $return_prf_msg['msgu']; //update?
                $json['content'][$count]['msga'] = $return_prf_msg['msga']; //áudio
                $json['content'][$count]['msgv'] = $return_prf_msg['msgv']; //vis
                $json['content'][$count]['notn'] = $return_prf_not['notn']; //new?
                $json['content'][$count]['notu'] = $return_prf_not['notu']; //update?
                $json['content'][$count]['nota'] = $return_prf_not['nota']; //áudio
                $json['content'][$count]['notv'] = $return_prf_not['notv']; //vis

                $count++;
            }


            $this->model_avatar->sync_user($this->userid, $_SERVER['REQUEST_TIME']);
            echo json_encode($json);
        }
    }

    /*
     * Função usada para carregar o arquivo de áudio e o arquivo de visemas, para cada mensagem e notificação.
     */
    private function load_files($_userid, $_courseid, $parameter, $_type, $_speaker, $_audio_format, $_avatar_lib) {
        $_return = "";

        /*
         * Se for uma notificação, verificar se a frase foi atualizada, se foi, sintetiza um novo audio
         */

        if ($_type == 'not') {

            $_POST["phrase"] = $_avatar_lib->assembleText($_courseid, $_userid);

            $_POST["speaker"] = $_speaker;
            $_POST["parameter"] = 1;
            $_POST["userid"] = $_userid;
            $_POST["courseid"] = $_courseid;
            $_POST["mobile"] = true;

            require_once($this->CFG->dirroot . '/blocks/avatar/synthesizer.php');
        }

        /*
         * Se existir algum log do usuário-curso, é pq tem wav e visema para carregar no json
         */
        if ($this->model_avatar->exists_user_cache($_userid, $_courseid, $_type)) {
            
            $log = $this->model_avatar->get_user_cache($_userid, $_courseid, $_type);

            $file = $this->CFG->tempPrefix;
            
            if ($parameter == 1) {
                $file .= "notifications_user" . $_userid . "_course" . $_courseid;
            } else if ($parameter == 2) {
                $file .= "message_course" . $_courseid;
            } else if ($parameter == 3) {
                $file .= "message_home";
            }

            /*
             * Se após a última sincronização, a mensagem tiver sido alterada/criada, ela será enviada por json agora,
             * pois ainda não foi sincronizada anteriormente.
             */

            if ($log->last_change >= $this->last_sync) {
                $_return[$_type . 'u'] = 1; //update = 1 porque são dados ainda não sincronizados
                //se a mensagem ainda não foi vista no moodle ela realmente é uma novidade
                if ($log->last_change >= $this->last_login_moodle) {
                    $_return[$_type . 'n'] = 1;
                } else {
                    //a mensagem não é uma novidade, mas será enviada mesmo assim, pois ainda não tem uma cópia no smartphone/tablet
                    $_return[$_type . 'n'] = 0;
                }

                /*
                 * Lendo arquivo de áudio
                 */
                $file_wav = "engine/temp/" . $file . "." . $_audio_format;
                $str_wav = $this->read_file($file_wav, "rb");

                $_return[$_type . 'a'] = base64_encode($str_wav);

                /*
                 * Lendo visemas
                 */
                $file_vis = $this->CFG->engine . '/temp/' . $file . ".vis";
                $str_vis = $this->read_file($file_vis, "r");
                $_return[$_type . 'v'] = $str_vis;
            } else {
                /*
                 * Mensagem já foi recebida antes
                 */
                $_return[$_type . 'n'] = 0;
                $_return[$_type . 'u'] = 0;
                $_return[$_type . 'a'] = "";
                $_return[$_type . 'v'] = "";
            }
        } else {
            /*
             * Não há nenhum registro de mensagem
             */
            $_return[$_type . 'n'] = 0;
            $_return[$_type . 'u'] = 0;
            $_return[$_type . 'a'] = "";
            $_return[$_type . 'v'] = "";
        }

        return $_return;
    }

    private function read_file($file, $mode) {
        $str = "";
        if (file_exists($file)) {
            if (($stream = fopen($file, $mode))) {
                while (!feof($stream) && connection_status() == 0) {
                    //reset time limit for big files
                    set_time_limit(0);
                    $str .= fread($stream, 1024 * 8);
                    flush();
                }
                fclose($stream);
            }
        }
        return $str;
    }

}

//HTTPS is potentially required in this page (future use)
//httpsrequired();
//$_POST['json'] = 'token';
//$_POST['json'] = 'content';
//$_POST['username'] = 'admin';
//$_POST['password'] = 'Vocallab&123';
//$_POST['username'] = 'admin';
//$_POST['password'] = '-Admin123-';
//$_POST['token'] = 'a4987860db8a4334e1cc40f64eabb31f';
//$_POST['token'] = 'bab11d3577f076c1aeda01aa565b0d0b';

//$_POST['username'] = 'ygor';
//$_POST['password'] = 'Admin123$$';

//$_POST['token'] = '234820e2e9bad8a76ab35db79a537778';

$avatar_mobile = new avatar_mobile(data_submitted());
?>
