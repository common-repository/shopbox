<?php
namespace ShopBox;

use GuzzleHttp\Client as GuzzleClient;

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class Api
{
    protected $token;
    protected $baseUrl;

    public function __construct()
    {
        $config = require(__DIR__.'/../config.php');
        
        if (file_exists(__DIR__.'/../config.local.php')) {
            $localConfig = require(__DIR__.'/../config.local.php');
            $config = array_merge($config, $localConfig);
        }

        $this->gzClient = new GuzzleClient([
            // 'base_uri' => 'api-dev.shopbox.com/api/v3/'
            'base_uri' => $config['api_base_url']
        ]);

        $this->baseUrl = $config['api_base_url'];
    }

    /**
     * @param string $token authentication token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    protected function logError($e) {
        return;
        echo "<pre>";
        echo Psr7\Message::toString($e->getRequest());
        
        if ($e->hasResponse()) {
            echo Psr7\Message::toString($e->getResponse());
        }
        
        echo "</pre>";
    }

    protected function get($url, $params = []) {
        if ($this->token) {
            $params['api-token'] = $this->token;
        }

        try {
            $response = $this->gzClient->get(
                $this->baseUrl.$url, [
                    'query' => $params
                ]
            );

            /* $result = file_get_contents($this->baseUrl.$url.'?'.http_build_query($params));
            
            if ($result === false) {
                throw new \Exception('failed to open url');
            }
            
            return json_decode($result); */
        } catch (RequestException $e) {
            $this->logError($e);
            throw $e;
        }

        return json_decode((string) $response->getBody());
        //  var_dump((string) $response->getBody()); die;
    }

    protected function post($url, $params, $query = []) {
        if ($this->token) {
            $query['api-token'] = $this->token; 
            // $query['accessToken'] = '4acf51b54f54ca334be80f0ef6652b22';
            // $query['client'] = 3413;
        }
        try {
            $response = $this->gzClient->post(
                $this->baseUrl.$url, [
                    'query' => $query,
                    'json' => $params
                ]
            );

            /* $postdata = json_encode($params);
            
            $opts = array('http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/json',
                    'content' => $postdata
                )
            );

            $context  = stream_context_create($opts);

            $result = file_get_contents($this->baseUrl.$url.'?'.http_build_query($query), false, $context);

            if ($result === false) {
                throw new \Exception('failed to open url');
            }
            return json_decode($result); */
        } catch (RequestException $e) {
            $this->logError($e);
            
            $message = "<pre>";
            $message .= Psr7\Message::toString($e->getRequest());
        
            if ($e->hasResponse()) {
                $message .= Psr7\Message::toString($e->getResponse());
            }
            $message .= "</pre>";
            throw new \Exception($message);
        }

        return json_decode((string) $response->getBody());
    }

    
    protected function put($url, $params, $query = []) {
        if ($this->token) {
            $query['api-token'] = $this->token;
        }
        try {
            $response = $this->gzClient->put(
                $this->baseUrl.$url, [
                    'query' => $query,
                    'json' => $params
                ]
            );

        } catch (RequestException $e) {
            $this->logError($e);
            $message = "<pre>";
            $message .= Psr7\Message::toString($e->getRequest());
        
            if ($e->hasResponse()) {
                $message .= Psr7\Message::toString($e->getResponse());
            }
            $message .= "</pre>";
            throw new \Exception($message);
        }

        return json_decode((string) $response->getBody());
    }

    
    public function getProducts($clientId = null)
    {
        return $this->get('products', [
            'client' => $clientId
        ]);
    }

    public function getTagProducts($tagId, $cashRegisterId)
    {
        return $this->get('tags/'.$tagId.'/products',[
            'cashRegister' => $cashRegisterId,
            'per-page' => -1
        ]);
    }

    public function getProductBranchInventory($branchId)
    {
        return $this->get('product-inventories', [
            'branch' => $branchId,
            'num_per_page' => 100
        ]);
    }

    public function createProductInventory($data)
    {
        return $this->post('product-inventories', $data);
    }

    public function getBranches($clientId = null)
    {
        return $this->get('branches', [
            'client' => $clientId
        ]);
    }
    
    public function getTags($filter = [])
    {
        return $this->get('tags', $filter);
    }
    
    public function getStaff()
    {
        return $this->get('roles', [
            'per-page' => -1
        ]);
    }

    public function getBranchDetails($branchId)
    {
        return $this->get('branches/'.$branchId);
    }
    
    public function getCashRegisterDetails($cashRegisterId)
    {
        return $this->get('cash-registers/'.$cashRegisterId);
    }
    
    public function getProduct($productId)
    {
        return $this->get('products/'.$productId);
    }

    public function getPaymentTypes()
    {
        return $this->get('payment-types', [
            'per-page' => -1
        ]);
    }

    public function createProduct($product)
    {
        return $this->post('products/create-or-update', $product);
    }

    public function createTag($tag)
    {
        return $this->post('tags', $tag);
    }

    public function uploadImage($path)
    {
        $response = $this->gzClient->request('POST', 'images', [
            'query' => [
                'api-token' => $this->token
            ],            
            'multipart' => [
                [
                    'name'     => 'image',
                    'contents' => fopen($path, 'r')
                ]
            ]
        ]);
        return json_decode((string) $response->getBody());
    }
    
    public function createBasket($data)
    {
        return $this->post('baskets', $data);
    }

    public function cancelBasket($basketId)
    {
        return $this->put('baskets/'.$basketId.'/cancel', ['woocommerce' => 1]);
    }

    public function createVariantType($data)
    {
        return $this->post('variance-type/create-or-update', $data);
    }

    public function getBasket($basketId)
    {
        return $this->get('baskets/'.$basketId);
    }

}
