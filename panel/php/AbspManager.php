<?php
namespace AbspFunctions;

/**
 * Asterisk Manager Interface (AMI) 操作クラス
 * 旧 functions.php, astman.php, amiauth.php を統合・効率化
 * PJSIP対応に伴い用語を Peer から Endpoint に変更
 */
class AbspManager {

    private $socket = false;
    private $error = "";
    private $host;
    private $port;
    private $username;
    private $password;

    /**
     * コンストラクタ
     */
    public function __construct($host, $username, $password, $port = 5038) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
        $this->connect();
    }

    /**
     * デストラクタ
     */
    public function __destruct() {
        $this->logout();
    }

    // --- Core AMI Methods ---

    private function connect() {
        $this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 3);
        if (!$this->socket) {
            $this->error = "Could not connect - $errstr ($errno)";
            return false;
        }

        stream_set_timeout($this->socket, 1);
        
        $wrets = $this->query("Action: Login\r\nUserName: {$this->username}\r\nSecret: {$this->password}\r\nEvents: off\r\n\r\n");

        if (strpos($wrets, "Message: Authentication accepted") !== false) {
            return true;
        } else {
            $this->error = "Could not login - Authentication failed";
            fclose($this->socket);
            $this->socket = false;
            return false;
        }
    }

    private function logout() {
        if ($this->socket) {
            fputs($this->socket, "Action: Logoff\r\n\r\n");
            fclose($this->socket);
            $this->socket = false;
        }
    }

    private function query($query) {
        if ($this->socket === false) return false;

        $wrets = "";
        fputs($this->socket, $query);
        
        $info = stream_get_meta_data($this->socket);
        while (($line = fgets($this->socket, 4096)) !== false && $info['timed_out'] == false) {
            $wrets .= $line;
            $info = stream_get_meta_data($this->socket);
            if ($line == "\r\n") break;
        }
        return $wrets;
    }

    public function getError() {
        return $this->error;
    }

    // AMI: Database Get
    public function getDB($family, $key) {
        $wrets = $this->query("Action: Command\r\nCommand: database get $family $key\r\n\r\n");
        $value = "";
        if ($wrets) {
            $value_start = strpos($wrets, "Value: ");
            if ($value_start !== false) {
                $value_start += 7;
                $value_stop = strpos($wrets, "\n", $value_start);
                $value = substr($wrets, $value_start, $value_stop - $value_start);
            }
        }
        return trim($value);
    }

    // AMI: Database Put
    public function putDB($family, $key, $value) {
        $wrets = $this->query("Action: Command\r\nCommand: database put $family $key $value\r\n\r\n");
        return (strpos($wrets, "Updated database successfully") !== false);
    }

    // AMI: Database Del
    public function delDB($family, $key) {
        $wrets = $this->query("Action: Command\r\nCommand: database del $family $key\r\n\r\n");
        return (strpos($wrets, "Database entry removed.") !== false);
    }

    // AMI: Database DelTree
    public function delDBTree($family) {
        $wrets = $this->query("Action: Command\r\nCommand: database deltree $family\r\n\r\n");
        return (strpos($wrets, "database entries removed.") !== false);
    }

    // AMI: Database Show
    public function getFamilyDB($family) {
        $wrets = $this->query("Action: Command\r\nCommand: database show $family\r\n\r\n");
        if (!$wrets) return false;

        $lines = explode("\n", $wrets);
        $value = [];
        $i = 0;
        foreach ($lines as $line) {
            if (strpos($line, "Response: Success") !== false) continue;
            if (strpos($line, "Message: Command") !== false) continue;
            if (strpos($line, "results found") !== false) break;
            
            $value[$i] = trim(str_replace("Output: /$family/", '', $line));
            $i++;
        }
        return count($value) > 0 ? $value : '';
    }

    public function execCMD($param) {
        return $this->query("Action: Command\r\nCommand: $param\r\n\r\n");
    }

    // 各種ビジネスロジック
    // 旧get_db_item等をキャメルケースに変更したもの(例:get_db_item -> getDbItem)

    public function getDbItem($item_name, $param) {
        return $this->getDB($item_name, $param);
    }

    public function putDbItem($item_name, $key, $param) {
        if (($item_name != '') && ($key != '')) {
            if ($param != '') {
                return $this->putDB($item_name, $key, "$param");
            } else {
                return $this->delDbItem($item_name, $key);
            }
        }
        return false;
    }

    public function delDbItem($item_name, $param) {
        if (($item_name != '') && ($param != '')) {
            $currentValue = $this->getDbItem($item_name, $param);
            //  値が存在する場合のみ削除を実行
            if ($currentValue != '') { 
                return $this->delDB($item_name, $param);
            }
            return true;
        }
        return false;
    }

    public function delDbTreeItem($item_name) {
        if ($item_name != '') {
            return $this->delDBTree($item_name);
        }
        return false;
    }

    public function getDbFamily($item_name) {
        return $this->getFamilyDB($item_name);
    }

    public function execCliCommand($param) {
        if ($param != '') {
            return $this->execCMD($param);
        }
        return '';
    }

    /**
     * エンドポイントとその内線情報取得
     * (旧 get_peer_info)
     * * @param string $endpoint エンドポイント名 (e.g. phone1)
     * @return array [endpoint=>..., exten=>...]
     */
    public function getEndpointInfo($endpoint) {
        $endpoint_info = ['endpoint' => $endpoint];
        
        $ext = $this->getDB('ABS/ERV', $endpoint);

        // 内線が割り当てられていないエンドポイントは処理しない
        if ($ext == '') {
            $endpoint_info['exten'] = "";
            $endpoint_info['limit'] = "";
            $endpoint_info['ogcid'] = "";
            $endpoint_info['pgrp']  = "";
        } else {
            $endpoint_info['exten'] = $ext;
            $endpoint_info['limit'] = $this->getDB('ABS/LMT', $endpoint);
            $endpoint_info['ogcid'] = $this->getDB("ABS/EXT/$ext", 'OGCID');
            $endpoint_info['pgrp']  = $this->getDB("ABS/EXT/$ext", 'PGRP');
        }
        return $endpoint_info;
    }

    /**
     * エンドポイントとその内線情報設定
     * (旧 set_peer_info)
     * * @param array $endpoint_info
     */
    public function setEndpointInfo($endpoint_info) {
        $p_endpoint  = $endpoint_info['endpoint']; // 処理対象のエンドポイント
        $p_exten     = $endpoint_info['exten'];
        $p_p_exten   = $endpoint_info['p_exten'] ?? '';
        $p_limit     = $endpoint_info['limit'];
        $p_ogcid     = $endpoint_info['ogcid'];
        $p_pgrp      = $endpoint_info['pgrp'];

        $retval = '';

        // 内線番号が指定されていない場合には該当するエンドポイントの情報を削除
        if ($p_exten == '') {
            $this->delDbItem('ABS/ERV', $p_endpoint);
            $this->delDbItem('ABS/LMT', $p_endpoint);
            if($p_p_exten) {
                $this->delDbItem("ABS/EXT/$p_p_exten", 'OGCID');
                $this->delDbItem("ABS/EXT/$p_p_exten", 'PGRP');
                $this->delDbItem('ABS/EXT', $p_p_exten);
            }
            $retval = '削除完了';
        } else if (ctype_digit($p_exten)) {
            // 指定された内線番号を使用しているエンドポイントを取得
            $p_c_endpoint = $this->getDB('ABS/EXT', $p_exten);
            
            if ($p_endpoint == $p_c_endpoint) { // 自分のエンドポイントと同じなら更新
                ($p_limit == '') ? $this->delDbItem('ABS/LMT', $p_endpoint) : $this->putDB('ABS/LMT', $p_endpoint, $p_limit);

                if ($p_ogcid == '') {
                    $this->delDbItem("ABS/EXT/$p_exten", 'OGCID');
                } elseif (ctype_digit($p_ogcid)) {
                    $this->putDbItem("ABS/EXT/$p_exten", 'OGCID', $p_ogcid);
                }

                if ($p_pgrp == '') {
                    $this->delDbItem("ABS/EXT/$p_exten", 'PGRP');
                } elseif (ctype_digit($p_pgrp)) {
                    $this->putDB("ABS/EXT/$p_exten", 'PGRP', $p_pgrp);
                }
                $retval = '変更完了';

            } else if ($p_c_endpoint == '') { // 使用しているエンドポイントがなければ新規
                $this->putDB('ABS/ERV', $p_endpoint, $p_exten);
                $this->putDB('ABS/EXT', $p_exten, $p_endpoint);

                if ($p_limit != '') $this->putDB('ABS/LMT', $p_endpoint, $p_limit);
                if ($p_ogcid != '' && ctype_digit($p_ogcid)) $this->putDB("ABS/EXT/$p_exten", 'OGCID', $p_ogcid);
                if ($p_pgrp != '' && ctype_digit($p_pgrp)) $this->putDB("ABS/EXT/$p_exten", 'PGRP', $p_pgrp);
                
                $retval = '登録完了';
            } else {
                $retval = '番号重複';
            }
        }
        return $retval;
    }

    public function getGroupInfo($grp) {
        $group_info = ['group' => $grp];
        $member = $this->getDB("ABS/GRP", "$grp");

        if ($member == '') {
            $group_info['member'] = "";
            $group_info['exten']  = "";
            $group_info['mode'] = "";
            $group_info['timeout'] = "";
            $group_info['ovr'] = "";
            $group_info['bnl'] = "";
            $group_info['bnt'] = "";
        } else {
            $group_info['member'] = $member;
            $group_info['exten']   = $this->getDB("ABS/GRP/$grp", "EXT");
            $group_info['mode']    = $this->getDB("ABS/GRP/$grp", "MET");
            $group_info['timeout'] = $this->getDB("ABS/GRP/$grp", "TMO");
            $group_info['ovr']     = $this->getDB("ABS/GRP/$grp", "OVR");
            $group_info['bnl']     = $this->getDB("ABS/GRP/$grp", "BNL");
            $group_info['bnt']     = $this->getDB("ABS/GRP/$grp", "BNT");
        }
        return $group_info;
    }

    public function setGroupInfo($group_info) {
        $p_member = $group_info['member'];
        $p_grp    = $group_info['group'];
        $p_mode   = $group_info['mode'];
        $p_exten  = $group_info['exten'];
        $p_timeout= $group_info['timeout'];
        $p_ovr    = $group_info['ovr'];
        $p_bnl    = $group_info['bnl'];
        $p_bnt    = $group_info['bnt'];

        if ($p_member == '') {
            $tmp_ext = $this->getDB("ABS/GRP/$p_grp", 'EXT');
            if ($tmp_ext != '' && $tmp_ext == "G$p_grp") {
                $this->delDbItem('ABS/EXT', $tmp_ext);
            }
            $this->delDbItem('ABS/GRP', $p_grp);
            $this->delDBTree("ABS/GRP/$p_grp");
            return '削除完了';
        } else {
            $tmp_ext = $this->getDB("ABS/GRP/$p_grp", 'EXT');
            if ($tmp_ext != '') {
                $tmp_ext2 = $this->getDB("ABS/EXT", $tmp_ext);
                if ($tmp_ext2 == "G$p_grp") $this->delDbItem('ABS/EXT', $tmp_ext);
            }
            $this->delDbItem('ABS/GRP', $p_grp);
            $this->delDBTree("ABS/GRP/$p_grp");

            $this->putDB('ABS/GRP', $p_grp, $p_member);
            if (ctype_digit($p_timeout)) $this->putDB("ABS/GRP/$p_grp", 'TMO', $p_timeout);
            if (ctype_digit($p_bnt)) $this->putDB("ABS/GRP/$p_grp", 'BNT', $p_bnt);
            $this->putDB("ABS/GRP/$p_grp", 'OVR', $p_ovr);
            $this->putDB("ABS/GRP/$p_grp", 'MET', $p_mode);
            $this->putDB("ABS/GRP/$p_grp", 'BNL', $p_bnl);

            if (ctype_digit($p_exten)) {
                $tmp = $this->getDB('ABS/EXT', $p_exten);
                if ($tmp == "G$p_grp") $tmp = '';
                if ($tmp == '') {
                    $this->putDB("ABS/GRP/$p_grp", 'EXT', $p_exten);
                    $this->putDB('ABS/EXT', $p_exten, "G$p_grp");
                    return '登録完了';
                } else {
                    return '内線重複';
                }
            }
            return '登録完了';
        }
    }

    public function getKeyInfo($key) {
        $key_info = ['key' => $key];
        $key_info['trunk'] = $this->getDB("KEYTEL/KEYSYS$key", "TRUNK");
        $key_info['rgrp']  = $this->getDB("KEYTEL/KEYSYS$key", "RING");
        $key_info['label'] = $this->getDB("KEYTEL/KEYSYS$key", "LABEL");
        $key_info['tech']  = $this->getDB("KEYTEL/KEYSYS$key", "TECH");
        $key_info['type']  = $this->getDB("KEYTEL/KEYSYS$key", "TYP");
        $key_info['ogcid'] = $this->getDB("KEYTEL/KEYSYS$key", "OGCID");
        $key_info['bpin']  = $this->getDB("KEYTEL/KEYSYS$key", "BPIN");
        $key_info['rgpt']  = $this->getDB("KEYTEL/KEYSYS$key", "RGPT");
        $key_info['mmd']   = $this->getDB("KEYTEL/KEYSYS$key", "MMD");
        return $key_info;
    }

    public function setKeyInfo($key_info) {
        $key = $key_info['key'];
        $fields = ['LABEL','TECH','TRUNK','TYP','OGCID','RGRP','RING','RGPT','BPIN','MMD'];
        foreach($fields as $f) {
            $this->delDbItem("KEYTEL/KEYSYS$key", $f);
        }

        $this->putDB("KEYTEL/KEYSYS$key", "LABEL", $key_info['label']);
        $this->putDB("KEYTEL/KEYSYS$key", "TECH", $key_info['tech']);
        $this->putDB("KEYTEL/KEYSYS$key", "TRUNK", $key_info['trunk']);
        $this->putDB("KEYTEL/KEYSYS$key", "TYP", $key_info['type']);
        $this->putDB("KEYTEL/KEYSYS$key", "MMD", $key_info['mmd']);
        
        if (ctype_digit($key_info['ogcid'])) {
            $this->putDB("KEYTEL/KEYSYS$key", "OGCID", $key_info['ogcid']);
        }
        $this->putDB("KEYTEL/KEYSYS$key", "RING", $key_info['rgrp']);
        $this->putDB("KEYTEL/KEYSYS$key", "RGPT", $key_info['rgpt']);
        if (ctype_digit($key_info['bpin'])) {
            $this->putDB("KEYTEL/KEYSYS$key", "BPIN", $key_info['bpin']);
        }
        return '登録完了';
    }

    public function createTsswList() {
        $retval = $this->getFamilyDB('ABS/TSSW');
        $r_list = [];
        $i = 0;
        if (is_array($retval)) {
            if (count($retval) > 2) {
                foreach ($retval as $line) {
                    if (strpos($line, "/") === false) {
                        list($t_cid, $dummy) = explode(":", $line, 2);
                        $r_list[$i] = trim($t_cid);
                        $i++;
                    }
                }
            }
        }
        return $r_list;
    }

    public function createTrunkList($option = '', $selected = '') {
        $select_list = "<option value=\"\" ></option>\n";
        
        $tret = $this->execCMD('pjsip show registrations');
        $tret = str_replace('Output: ', '', $tret);
        $tlist = explode("\n", $tret);
        
        $trlist = [];
        $i = 0;
        if (count($tlist) !== 0) {
            foreach ($tlist as $line) {
                if (strpos($line, "/") !== false) {
                    if ($line != '') {
                        list($tmp, $dummy) = explode('/', $line, 2);
                        $trlist[$i] = trim($tmp);
                        $i++;
                    }
                }
            }
            for ($j = 0; $j < $i; $j++) {
                 $sel = ($trlist[$j] == $selected) ? 'selected' : '';
                 $select_list .= "<option value=\"{$trlist[$j]}\" $sel>{$trlist[$j]}</option>\n";
            }
        }
        return $select_list;
    }

    public function getPgrpMember($grp) {
        return $this->getDB("ABS/PGRP", "$grp");
    }

    public function setPgrpMember($pgrp, $member) {
        if ($member == '') {
            $this->delDbItem('ABS/PGRP', $pgrp);
            return '削除完了';
        } else {
            $this->putDB('ABS/PGRP', $pgrp, $member);
            return '登録完了';
        }
    }

    public function getNksTech($num) { return $this->getDB("ABS/NKS$num", "TECH"); }
    public function getNksTrunk($num) { return $this->getDB("ABS/NKS$num", "TRUNK"); }
    public function getNksType($num) { return $this->getDB("ABS/NKS$num", "TYP"); }
    public function getOgpNum($num) { return $this->getDB("ABS", "OGP$num"); }
    
    public function getOgpRoute($num) {
        if ($this->getDB("ABS/OGP$num", "NKS") != '') return "NKS";
        if ($this->getDB("ABS/OGP$num", "KEY") != '') return "KEY";
        return "";
    }

    public function getOgpRouteNum($num) {
        $val = $this->getDB("ABS/OGP$num", "NKS");
        if ($val != '') return $val;
        return $this->getDB("ABS/OGP$num", "KEY");
    }

    public function getOgpOgcid($num) { return $this->getDB("ABS/OGP$num", "OGCID"); }
    public function getAecCodes() { return $this->getDB("ABS", "AEC"); }

    /**
     * ターゲットリスト（内線、FAP、グループ、ローカル）の配列を取得
     * * @return array
     */
    public function getTargetList() {
        $list = [];

        // 1. 通常内線 (phone1 - phone32) テクノロジはPJSIP固定
        for ($i = 1; $i <= 32; $i++) {
            $ext = $this->getDB("ABS/ERV", "PJSIP/phone$i");
            if ($ext != '') {
                $list[] = ['type' => 'ext', 'value' => $ext, 'label' => $ext];
            }
        }
        
        // 2. フリーアドレス内線 (FAP)
        $fap_list = $this->getFamilyDB("ABS/FAP/UID");
        if (!empty($fap_list) && is_array($fap_list)) {
            foreach ($fap_list as $fap_ent) {
                if (strpos($fap_ent, '/EXT') !== false) {
                    list($dummy, $fap_ext) = explode(':', $fap_ent, 2);
                    $list[] = ['type' => 'fap', 'value' => $fap_ext, 'label' => "$fap_ext(F)"];
                }
            }
        }
        
        // 3. グループ (G1 - G16)
        for ($i = 1; $i <= 16; $i++) {
            $member = $this->getDB("ABS/GRP", "$i");
            if ($member != '') {
                $list[] = ['type' => 'group', 'value' => "G$i", 'label' => "G$i"];
            }
        }
        
        // 4. ローカル着信
        $ext = $this->getDB("ABS/ERV", "localring");
        if ($ext != "") {
            $list[] = ['type' => 'local', 'value' => $ext, 'label' => "$ext(L)"];
        }

        return $list;
    }

    /**
     * トランク一覧を配列で取得
     * @return array
     */
    public function getTrunkList() {
        $trunks = [];

        // コマンド実行
        $tret = $this->execCMD('pjsip show registrations');
        
        // 結果が空、またはエラーの場合は空配列を返す
        if (!$tret) {
            return $trunks;
        }

        $lines = explode("\n", $tret);

        foreach ($lines as $line) {
            // 1. まず行の前後の空白を除去
            $line = trim($line);

            // 2. Asterisk AMI特有の "Output: " 接頭辞を削除
            //    これを行わないと、後続の判定やすべてのパースがズレます
            $line = str_replace('Output: ', '', $line);
            
            // 3. 削除後に再度トリム（"Output: " の後にスペースがあった場合のため）
            $line = trim($line);

            // 4. 無視すべき行をスキップ
            //    空行、AMIヘッダー(Response/Privilege)、CLIヘッダー(<Registration...)、
            //    区切り線(=)、集計行(Objects found)、終了タグ等を弾く
            if (empty($line) ||
                strpos($line, 'Response:') === 0 ||
                strpos($line, 'Privilege:') === 0 ||
                strpos($line, '<Registration') === 0 || 
                strpos($line, '=') === 0 || 
                strpos($line, 'Objects found') === 0 ||
                strpos($line, '--END COMMAND--') !== false
            ) {
                continue;
            }

            // 5. 有効な行であれば "TrunkName/sip:..." の形式をパース
            if (strpos($line, "/") !== false) {
                // '/' で分割してトランク名(前半)を取得
                list($name, $dummy) = explode('/', $line, 2);
                $name = trim($name);
                
                // トランク名が空でなく、かつ未登録なら配列に追加
                if ($name !== '' && !in_array($name, $trunks)) {
                    $trunks[] = $name;
                }
            }
        }
        
        return $trunks;
    }

    /**
     * トランクスイッチャ一覧を配列で取得
     * * @return array
     */
    public function getTsswList() {
        $retval = $this->getFamilyDB('ABS/TSSW');
        $list = [];
        if (is_array($retval) && count($retval) > 0) {
            foreach ($retval as $line) {
                if (strpos($line, "/") === false) {
                    list($t_cid, $dummy) = explode(":", $line, 2);
                    $list[] = trim($t_cid);
                }
            }
        }
        return $list;
    }

    /**
     * MACアドレスを正規化する (区切り文字なし大文字12桁)
     * @param string $input
     * @return string|false 成功時は正規化された文字列、失敗時はfalse
     */
    public function normalizeMacAddress($input) {
        $clean = preg_replace('/[^a-fA-F0-9]/', '', $input);
        if (strlen($clean) === 12) {
            return strtoupper($clean);
        }
        return false;
    }

    /*
     * MACアドレスを表示用に整形する (例: AABB... -> AA:BB:...)
     * @param string $mac raw string
     * @return string Formatted MAC address
     */
    public function formatMacAddress($mac) {
        // まず念のため余計な文字を除去
        $clean = preg_replace('/[^a-fA-F0-9]/', '', $mac);
    
        // 12桁の場合のみ整形して返す
        if (strlen($clean) === 12) {
            // 2文字ずつ分割してコロンで結合し、大文字にする
            return strtoupper(implode(':', str_split($clean, 2)));
        }
    
        // データが不完全な場合や空の場合はそのまま返す
        return $mac;
    }

    /**
     * 着信先、転送先などで使用可能なターゲット一覧（内線、FAP、グループ、Local）を取得
     * * @return array [['value' => '201', 'label' => '201 (PJSIP/phone1)'], ...]
     */
    public function getExtAndGroupList() {
        $targets = [];

        // ---------------------------------------------------------
        // 1. 通常内線 (ABS/EXT)
        // ---------------------------------------------------------
        $ext_entries = $this->getFamilyDB('ABS/EXT');
        if (is_array($ext_entries)) {
            foreach ($ext_entries as $line) {
                // "Key : Value" を分割 (例: "201 : PJSIP/phone1")
                $parts = explode(' : ', $line, 2);
                if (count($parts) < 2) continue;

                $ext_num = trim($parts[0]);
                $destination = trim($parts[1]);

                // サブキー (例: 201/OGCID) は除外
                if (strpos($ext_num, '/') !== false) {
                    continue;
                }

                // ラベル生成
                $targets[$ext_num] = [
                    'value' => $ext_num,
                    'label' => "$ext_num ($destination)"
                ];
            }
        }

        // ---------------------------------------------------------
        // 2. フリーアドレス内線 (ABS/FAP) [今回追加]
        // ---------------------------------------------------------
        // database show ABS/FAP/UID の結果から抽出
        // 取得例: "3001/EXT : 3001" (プレフィクス除去後)
        $fap_entries = $this->getFamilyDB('ABS/FAP/UID');
        if (is_array($fap_entries)) {
            foreach ($fap_entries as $line) {
                // "UID/xxxx/EXT" のエントリのみ対象とする
                if (strpos($line, '/EXT') !== false) {
                    $parts = explode(' : ', $line, 2);
                    if (count($parts) < 2) continue;

                    $fap_ext = trim($parts[1]); // 値の部分 (例: 3001)

                    // 既にABS/EXTで取得済みの場合でも、FAPであることを明示するために上書きする、
                    // または未登録の場合のみ追加するなど運用に合わせて調整可能。
                    // ここでは「FAP定義があれば (F) 表記で上書き」する挙動にします。
                    $targets[$fap_ext] = [
                        'value' => $fap_ext,
                        'label' => "$fap_ext (F)"
                    ];
                }
            }
        }

        // ---------------------------------------------------------
        // 3. グループ (ABS/GRP)
        // ---------------------------------------------------------
        // ABS/EXT に登録されていないグループも漏れなく拾う
        for ($i = 1; $i <= 16; $i++) {
            $g_key = "G$i";
            if (!isset($targets[$g_key])) {
                $grp_mem = $this->getDbItem("ABS/GRP", "$i");
                if ($grp_mem !== '') {
                    $targets[$g_key] = [
                        'value' => $g_key,
                        'label' => "$g_key (Group)"
                    ];
                }
            }
        }

        // ---------------------------------------------------------
        // 4. ローカル着信 (Local)
        // ---------------------------------------------------------
        $local_ring = $this->getDbItem("ABS/ERV", "localring");
        if ($local_ring != "") {
            $targets[$local_ring] = [
                'value' => $local_ring,
                'label' => "$local_ring (Local)"
            ];
        }

        // 内線番号順にソート
        ksort($targets, SORT_NATURAL);

        return array_values($targets);
    }
}
?>
