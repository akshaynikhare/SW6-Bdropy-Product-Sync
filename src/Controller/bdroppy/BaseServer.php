<?php

namespace slox_product_sync\Controller\bdroppy;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use slox_product_sync\Util\DebugLog;

class BaseServer
{
    protected $mainUrl = 'https://prod.bdroppy.com/';
    protected $url = null;
    protected $BearerToken = null;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;


    /**
     * @var DebugLog
     */
    private $debugLog;

    public function __construct(
        SystemConfigService $systemConfigService,
        DebugLog $debugLog
    ) {
        $this->systemConfigService  = $systemConfigService;
        $this->debugLog = $debugLog;
        $this->init();
    }

    protected function init($prefix = null)
    {
        $prefix = is_null($prefix) ? 'api/' : $prefix;
        $this->url = $this->mainUrl . $prefix;
        $this->BearerToken = $this->systemConfigService->get('slox_product_sync.config.BearerToken');

        if(empty($this->BearerToken) or is_null($this->BearerToken)){
            //TODO: add precaustionarry code
            $this->getNewToken($this->systemConfigService->get('slox_product_sync.config.user'),$this->systemConfigService->get('slox_product_sync.config.password'));
        }
    }

    protected function post($url, $dataArray ,$useBearerToken = true)
    {
        if($useBearerToken){
            return $this->get_data_by_posting($this->url . $url, $dataArray,array('authorization:Bearer ' .$this->BearerToken ));
        }
        return $this->get_data_by_posting($this->url . $url, $dataArray);
    }

    protected function get($url,$useBearerToken = true)
    {
        if($useBearerToken){
            return $this->get_data_by_getting($this->url . $url ,array('authorization:Bearer ' .$this->BearerToken ));
        }
        return $this->get_data_by_getting($this->url . $url);
    }

    static function get_data_by_posting(String $url, array $post_parameters ,array $post_header = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS,  json_encode($post_parameters));

        if($post_header){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Content-Type' => 'application/json'], $post_header));
        }else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = (array) json_decode($result, true);
        $result = array_merge(['HTTP_CODE' => $httpcode], $result);

        return $result;
    }

    static function get_data_by_getting(String $url, array $post_header = null)
    {
        $ch = curl_init($url);

        if($post_header){
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(['Content-Type' => 'application/json'], $post_header));
        }else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET"); 

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $result = (array) json_decode($result, true);
        $result = array_merge(['HTTP_CODE' => $httpcode, 'result'=>$result]);

        return $result;
    }


    public function getNewToken($email, $password)
    {
        $result = $this->post("auth/login", array(
            'email' => $email,
            'password' => $password
        ), false);

        if ($result['HTTP_CODE'] == 200) {
            $this->debugLog->sendLog('Bdroppy change token bdroppy token refresh is successful !');

            $this->systemConfigService->set('slox_product_sync.config.BearerToken',$result['token']);
            $this->BearerToken = $result['token'];
            return array("api-token" => $result['token'], "api-token-for-user" => $result['email']);
        } else {
          $this->debugLog->sendLog('Bdroppy change token bdroppy token refresh isn\'t successful ! -- response status : ' . $result['code']);
          return array("error" => $result['HTTP_CODE'] , "message" => $result['code']);
        }
    }

    public function getUserCatalogIdByName($catalogName)
    {
        $response = $this->get("user_catalog/list",  true);

        if(is_array($response["result"]) &&  count($response["result"])>0 ){
            foreach($response["result"] as $catalog){
                if($catalog["name"]==$catalogName){
                   return $catalog["_id"];
                }
            }
        }
        return null;
    }

    public function getArticeArrayByCatalogId($catalogID)
    {
        if($catalogID!=null){
            $result = $this->get("product/export?user_catalog=".$catalogID,  true);
            if(is_array($result["result"]["items"]) &&  count($result["result"]["items"])>0 ){
                       //return $result["result"]["items"];

                       return array_slice($result["result"]["items"],count($result["result"]["items"])-20);
            }
        }
        return null;
    }

    public function getAllCategories()
    {
        $response = $this->get('category');
        if(is_array($response["result"]) &&  count($response["result"])>0 ){
            $result = [];
            foreach ($response['result'] as $item){
                $result[$item["_id"]] = [
                    'id'=>$item["_id"],
                    'code'=>$item["code"]
                ];
            }
            return $result;
        }
        return null;
    }

    public function getAllCategoriesWithSubCategories()
    {
        $longCategoriesList=[];
        $Categories = $this->getAllCategories();
        foreach ($Categories as $Categorie){
            $SubCategories=$this->getSubCategories($Categorie["code"]);
    
            
            if($SubCategories){
                foreach ($SubCategories as $SubCategorie){
                    $longCategoriesList[$SubCategorie["id"]] = [
                        'value'=>$SubCategorie["id"],
                        'label'=>$Categorie["code"]." > ".$SubCategorie["code"],
                        'code'=>$SubCategorie["code"],
                        'parent_code'=>$Categorie["code"]
                    ];
                }
            }        
        }
        return $longCategoriesList;
    }

    public function getSubCategories($category)
    {
        $response = $this->get('subcategory?tag_4='.$category);
        if(is_array($response["result"]) &&  count($response["result"])>0 ){
            $result = [];
            foreach ($response['result'] as $item){
                $result[$item["_id"]] = [
                    'id'=>$item["_id"],
                    'code'=>$item["code"]
                ];
            }
            return $result;
        }
        return null;

    }

    
}
