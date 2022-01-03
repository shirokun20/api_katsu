<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use Firebase\JWT\JWT;
use App\Models\PenjualanModel;
use App\Models\PenDetailModel;
use App\Models\JurnalModel;
class Penjualan extends ResourceController
{

    private $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }
    /**
     * Check Autorization
     */
    private function _checkLevel() 
    {
        $key = getenv('TOKEN_SECRET');
        $header = $this->request->getServer('HTTP_AUTHORIZATION');
        if(!$header) return $this->failUnauthorized('Token Required');
        $token = explode(' ', $header)[1];
 
        try {
            $decoded = JWT::decode($token, $key, ['HS256']);
            $response = $decoded->data;
            return $response;
        } catch (\Throwable $th) {
            return $this->fail('Token tidak valid!!');
        }
    }
    /**
     * Primary function of status pembayaran
     */
    public function statusPembayaran()
    {
        $this->_checkLevel();
        $data = $this->db
                      ->table('status_pembayaran')
                      ->select('status_pembayaran_id as status_id, status_pembayaran_nama as status_text', false)
                      ->get();
        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $data->getResult(),
        ]);
    }
    /**
     * Primary function of status pemesanan
     */
    public function statusPemesanan()
    {
        $this->_checkLevel();
        $data = $this->db
                      ->table('status_pemesanan')
                      ->select('status_pemesanan_id as status_id, status_pemesanan_nama as status_text', false)
                      ->get();
        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $data->getResult(),
        ]);
    }
    /**
     * Sub Function from show Data
     */
    private function _showDataQuery($limit, $page, $getLevel) 
    {
        // request 
        $search     = $this->request->getGet('search') != "" ? $this->request->getGet('search') : "";
        // status bayar dan status pemesanan
        $stbyr      = $this->request->getGet('stbyr') != "" ? $this->request->getGet('stbyr') : "";
        $stpem      = $this->request->getGet('stpem') != "" ? $this->request->getGet('stpem') : "";
        // start date dan end date
        $startDate  = $this->request->getGet('startDate') != "" ? $this->request->getGet('startDate') : "";
        $endDate    = $this->request->getGet('endDate') != "" ? $this->request->getGet('endDate') : "";
        // 
        $model = new PenjualanModel();
        // Query
        $sql = $this->db->table('penjualan pj');
        $model->select($sql);
        $model->relation($sql);
        // filter Tanggal
        $model->filterDate($sql, [
            'startDate' => $startDate,
            'endDate'   => $endDate,
        ]);
        // search
        $model->searchData($sql, $search);
        // 
        if ($getLevel->level != "Admin") $sql->where(['pj.pengguna_id' => $getLevel->id]);
        if ($stbyr) $sql->where(['pj.status_pembayaran_id' => $stbyr]);
        if ($stpem) $sql->where(['pj.status_pemesanan_id' => $stpem]);
        //
        $sql->orderBy('pj.penjualan_waktu', 'desc');
        $sql->orderBy('pj.penjualan_nota', 'desc');
        // Jika ada params status
        $sql->limit($limit, $page * $limit);
        // result
        $result = $sql->get();
        return $this->_showSubDataQuery($result->getResult());
    }
    /**
     * Sub Function from show Sub Data
     */
    private function _showSubDataQuery($result) 
    {
        $model = new PenDetailModel();
        //
        $data = [];
        foreach ($result as $value) {
            $r = [];
            $r['pjnota']        = $value->pjnota;
            $r['pjwaktu']       = $value->pjwaktu;
            $r['pjsubtotal']    = $value->pjsubtotal;
            $r['pjongkir']      = $value->pjongkir;
            $r['pjtotal']       = $value->pjtotal;
            $r['pjketerangan']  = $value->pjketerangan;
            $r['pjmetpem']      = $value->pjmetpem;
            $r['pjstbyrid']     = $value->pjstbyrid;
            $r['pjstpemid']     = $value->pjstpemid;
            //
            $r['pnama']         = $value->pnama;
            //
            $r['stbyrnm']       = $value->stbyrnm;
            $r['stpemnm']       = $value->stpemnm;
            //
            $r['dpj']           = [];
            $output = $model->getData(['penjualan_nota' => $r['pjnota']]);
            if ($output->getNumRows() > 0) $r['dpj'] = $output->getResult();
            $data[] = $r;
        }
        return $data;
    }
    /**
     * Primary Function show data
     */
    use ResponseTrait;
    public function showData()
    {
        $getLevel = $this->_checkLevel();
        // if ($getLevel->level == "Admin") {
        // request
        $limit  = $this->request->getGet('limit') != "" ? $this->request->getGet('limit') : 5;
        $page   = $this->request->getGet('page') != "" ? $this->request->getGet('page') : 0;
        //
        return $this->setResponseFormat('json')->respond([
            'status' => 200,
            'error' => false,
            'data' => $this->_showDataQuery($limit, $page, $getLevel),
            'request' => [
                $this->request->getGet(),
            ]
        ]);
        // };
        return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
    }
    /**
     * Primary Function add data
     */
    use ResponseTrait;
    public function addData()
    {
        $getLevel = $this->_checkLevel();
        //
        $validation =  \Config\Services::validation();
        $validation->setRules([
            'pjmetpem'    => ['label' => 'Metode Pembayaran', 'rules' => 'required'],
            'dpj'         => ['label' => 'Detail Penjualan', 'rules' => 'required'],
        ]);

        if (!$validation->withRequest($this->request)->run()) return $this->fail($validation->getErrors());
        $model = new PenjualanModel();
        //
        $req['pengguna_id']                 = $getLevel->id;
        $req['penjualan_metode_pembayaran'] = $this->request->getVar('pjmetpem');
        if ($this->request->getVar('pjongkir'))
            $req['penjualan_ongkir']        = $this->request->getVar('pjongkir');
        else $req['penjualan_ongkir']       = 0;
        if ($this->request->getVar('pjketerangan'))
            $req['penjualan_keterangan']    = $this->request->getVar('pjketerangan');
        $req['penjualan_sub_total']         = $this->_calculate_sub_total();
        $req['penjualan_total']             = $req['penjualan_ongkir'] + $req['penjualan_sub_total'];
        // jika input status
        if ($this->request->getVar('stbyrid'))
            $req['status_pembayaran_id']    = $this->request->getVar('stbyrid');
        else $req['status_pembayaran_id']   = 1;
        if ($this->request->getVar('stpemid'))
            $req['status_pemesanan_id']     = $this->request->getVar('stpemid');
        else $req['status_pemesanan_id']    = 4;
        // Penutup jika input status
        $req['penjualan_nota']              = $model->getUniqCode($req['pengguna_id']);
        if ($model->save($req)) {
            // ketika status "Ya"
            // if ($this->request->getVar('pmisvalid') == "Ya") 
                // $this->_tambahStok($req['produk_kode'], $req['restok_produk_jumlah']);
            $kurangi_stok = false;
            if ($this->request->getVar('stbyrid') == 2 && $this->request->getVar('stpemid') == 2) $kurangi_stok = true;
            $this->_addToDetail($req, $kurangi_stok);
            if ($kurangi_stok) $this->_tambahCatatanKeKas($req); 
                
            return $this->setResponseFormat('json')->respond([
                'status' => 200,
                'error' => false,
                'message' => [
                    "success" => "Berhasil mengisi data transaksi penjualan..!",
                    "nota" => $req['penjualan_nota'],
                ],
            ]);
        }
        else return $this->fail("Gagal mengisi data transaksi penjualan..!");
    }
     /**
     * Sub Function tambah catatan ke kas
     */
    private function _tambahCatatanKeKas($req)
    {
        $model = new JurnalModel();
        $data[] = [
            'jurnal_kode' => $model->getUniqCode(1),
            'rekening_kode' => 1,
            'jurnal_ref_kode' => '101',
            'jurnal_debet' => $req['penjualan_total'],
            'jurnal_kredit' => 0,
            'jurnal_keterangan' => 'Debit dari Kas dengan Nota Penjualan Produk: ' . $req['penjualan_nota'] . ' (Sudah termasuk ongkir!)',
        ];

        $data[] = [
            'jurnal_kode' => $model->getUniqCode(4),
            'rekening_kode' => 4,
            'jurnal_ref_kode' => '401',
            'jurnal_kredit' => $req['penjualan_total'],
            'jurnal_debet' => 0,
            'jurnal_keterangan' => 'Kredit ke Aset (produk) dengan Nota Penjualan Produk: ' . $req['penjualan_nota'] .  ' (Sudah termasuk ongkir!)',
        ];
        $sql = $this->db->table('jurnal');
        $sql->insertBatch($data);
    }
    /**
     * Sub Function get sub total
     */
    private function _calculate_sub_total()
    {
        $dpj = [];
        if ($this->request->getVar('dpj')) $dpj = $this->request->getVar('dpj');
        $subtotal = 0;
        foreach ($dpj as $key) {
            $harga = $key->dpjph;
            $qty   = $key->dpjpq;
            $subtotal += $harga * $qty;
        }
        return $subtotal;
    }
    /**
     * Sub Function add to detail
     */
    private function _addToDetail($req, $kurangi_stok = false)
    {
        if ($this->request->getVar('dpj')) $dpj = $this->request->getVar('dpj');
        $data = [];
        foreach ($dpj as $key) {
            $r = [];
            $harga = $key->dpjph;
            $qty   = $key->dpjpq;
            $r['detail_penjualan_total']   = $harga * $qty;
            $r['detail_penjualan_qty']     = $qty;
            $r['detail_penjualan_harga']   = $harga;
            $r['produk_kode']              = $key->dpjpk;
            $r['penjualan_nota']           = $req['penjualan_nota'];
            if ($kurangi_stok) $this->_kurangiStok($r['produk_kode'], $r['detail_penjualan_qty']);
            $data[] = $r;
        }
        $sql = $this->db->table('detail_penjualan');
        $sql->insertBatch($data);
    }
    /**
     * Sub Function detail Penjualan untuk edit/update data
     */
    private function _getDetailToChangeStok($nota)
    {
        $tabel = $this->db->table('detail_penjualan');
        $res = $tabel->where(['penjualan_nota' => $nota])->get();
        foreach ($res->getResultArray() as $key) {
            $r['produk_kode']              = $key['produk_kode'];
            $r['produk_stok']              = $key['detail_penjualan_qty'];
            $this->_kurangiStok($r['produk_kode'], $r['produk_stok']);
        }

    }
    /**
     * Sub Function update to stok produk
     */
    private function _kurangiStok($pkkode, $jml = 0) 
    {
        $sql = $this->db->table('produk');
        $sql->set('produk_stok', 'produk_stok-'.$jml, false);
        $sql->where('produk_kode', $pkkode);
        $sql->update();
    }
    /**
     * Primary Function 
     */
    use ResponseTrait;
    public function updateData()
    {
        $getLevel = $this->_checkLevel();
        // if ($getLevel->level != "Admin") return $this->fail("Maaf anda tidak bisa mengakses halaman ini!!");
        $check = $this->_beforeUpdateData($this->request->getVar('pjnota')); 
        if ($check['status']) {
            $model = new PenjualanModel();
            $req['penjualan_metode_pembayaran'] = $this->request->getVar('pjmetpem');
            if ($this->request->getVar('pjongkir') > -1)
                $req['penjualan_ongkir']        = $this->request->getVar('pjongkir');
            else $req['penjualan_ongkir']       = $check['ongkir'];
            if ($this->request->getVar('pjketerangan'))
                $req['penjualan_keterangan']    = $this->request->getVar('pjketerangan');
            $req['penjualan_total']             = $req['penjualan_ongkir'] + $check['sub_total'];
            // jika input status 
            if ($this->request->getVar('stbyrid'))
                $req['status_pembayaran_id']    = $this->request->getVar('stbyrid');
            else $req['status_pembayaran_id']   = $check['stbyrid'];
            if ($this->request->getVar('stpemid'))
                $req['status_pemesanan_id']     = $this->request->getVar('stpemid');
            else $req['status_pemesanan_id']   = $check['stpemid'];
            // penutup jika input status 
            $req['penjualan_nota']              = $check['pn'];
            if ($model->save($req)) {
                // ketika status "Ya"
                // if ($this->request->getVar('pmisvalid') == "Ya") 
                    // $this->_tambahStok($req['produk_kode'], $req['restok_produk_jumlah']);
                $kurangi_stok = false;
                if ($req['status_pembayaran_id'] == 2 && $req['status_pemesanan_id'] == 2) $kurangi_stok = true;
                if ($kurangi_stok) {
                    $this->_getDetailToChangeStok($req['penjualan_nota']);
                    $this->_tambahCatatanKeKas($req);
                } 
                    
                return $this->setResponseFormat('json')->respond([
                    'status' => 200,
                    'error' => false,
                    'message' => [
                        "success" => "Berhasil mengubah data transaksi penjualan..!",
                        "nota" => $req['penjualan_nota'],
                    ],
                ]);
            } return $this->fail("Gagal mengisi data transaksi penjualan..!");
        }
        else return $this->fail($check['message']);
    }

    /**
     * Sub Function before update data
     * Lumayan susah bagian ini!!!
     * dikarenakan banyak logika kemungkinan kemungkinan yang cukup rumit
     */
    private function _beforeUpdateData($id) 
    {
        $model = new PenjualanModel();
        $checkData = $model->where(['penjualan_nota' => $id])->get();
        if (!$checkData->getNumRows()) return [
            'status' => false,
            'message' => 'Nota tidak ditemukan!!!',
        ];

        else {
            $status = true;
            $message = "";
            $check = $checkData->getRowArray();
            //
            if ($check['status_pembayaran_id'] == "2" && $check['status_pemesanan_id'] == "2") {
                $status = false;
                $message = "Tidak bisa mengubah transaksi yang sudah selesai dan lunas!!";
            }
            //
            return [
                'status'    => $status,
                'message'   => $message,
                'pn'        => $check['penjualan_nota'],
                'ongkir'    => $check['penjualan_ongkir'],
                'sub_total' => $check['penjualan_sub_total'],
                'stbyrid'   => $check['status_pembayaran_id'],
                'stpemid'   => $check['status_pemesanan_id'],
            ];
        }
    }
}
