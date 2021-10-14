<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\PenggunaModel;
use Firebase\JWT\JWT;
class Login extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    use ResponseTrait;
    public function index()
    {
        // helper(['form']);
        $validation =  \Config\Services::validation();
        $validation->setRule('nohp', 'Nomor Handphone', 'required');
        
        if (!$validation->withRequest($this->request)->run()) return $this->fail($validation->getErrors());
        //
        $model = new PenggunaModel();

        $user = $model->where(['pengguna_nohp' => $this->request->getVar('nohp')])->first();

        if (!$user) return $this->failNotFound('Nomor Handphone tidak ditemukan!!');

        if ($user['status_pengguna_id'] == '1') {
            $key = getenv('TOKEN_SECRET');
            $issuer_claim 		= "THE_CLAIM"; // this can be the servername. Example: https://domain.com
            $audience_claim 	= "THE_AUDIENCE";
            $issuedat_claim 	= time(); // issued at
            $notbefore_claim 	= $issuedat_claim + 2; //not before in seconds
            $expire_claim 		= $notbefore_claim + 3600;
            $payload = array(
                'iss' 	=> $issuer_claim,
                'aud' 	=> $audience_claim,
                'iat' 	=> $issuedat_claim,
                'nbf' 	=> $notbefore_claim,
                'exp' 	=> $expire_claim,
                "data" => [
                    "id" => (int) $user['pengguna_id'],
                    "nohp" => $user['pengguna_nohp'],
                    "level" => $user['pengguna_jenis'],
                ]
            );

            $token = JWT::encode($payload, $key);
            return $this->setResponseFormat('json')->respond([
                'status' => 200,
                'error' => false,
                'token' => $token,
            ]);
        }
        if ($user['status_pengguna_id'] == '3') return $this->fail('Menunggu diaktifkan admin!!');
        
        return $this->fail('Akun anda sedang disuspend!!');
    }
}
