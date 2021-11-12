<?php

namespace App\Models;

use CodeIgniter\Model;

class PenjualanModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'penjualan';
    protected $primaryKey           = 'penjualan_nota';
    protected $useAutoIncrement     = false;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'penjualan_nota',
        'penjualan_waktu',
        'penjualan_sub_total',
        'penjualan_ongkir',
        'penjualan_total',
        'penjualan_keterangan',
        'penjualan_metode_pembayaran',
        'penjualan_bukti_transfer',
        'pengguna_id',
        'status_pembayaran_id',
        'status_pemesanan_id',
    ];

    // Dates
    protected $useTimestamps        = false;
    protected $dateFormat           = 'datetime';
    protected $createdField         = 'created_at';
    protected $updatedField         = 'updated_at';
    protected $deletedField         = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks       = true;
    protected $beforeInsert         = [];
    protected $afterInsert          = [];
    protected $beforeUpdate         = [];
    protected $afterUpdate          = [];
    protected $beforeFind           = [];
    protected $afterFind            = [];
    protected $beforeDelete         = [];
    protected $afterDelete          = [];

    public function select($sql)
    {
        $sql->select('pj.penjualan_nota as pjnota');
        $sql->select('pj.penjualan_waktu as pjwaktu');
        $sql->select('pj.penjualan_total as pjtotal');
        $sql->select('pj.penjualan_sub_total as pjsubtotal');
        $sql->select('pj.penjualan_ongkir as pjongkir');
        $sql->select('pj.penjualan_keterangan as pjketerangan');
        $sql->select('pj.penjualan_metode_pembayaran as pjmetpem');
        $sql->select('pj.pengguna_id as pid');
        $sql->select('pgn.pengguna_nama as pnama');
        $sql->select('stbyr.status_pembayaran_nama as stbyrnm');
        $sql->select('stpem.status_pemesanan_nama as stpemnm');
    }

    public function filterDate($sql, $date)
    {
        if ($date['startDate'] && $date['endDate']) {
            $sql->where('DATE(pj.penjualan_waktu) between "' . $date['startDate'] . '" and "' . $date['endDate'] . '"');
        } elseif ($date['startDate']) {
            $sql->where('DATE(pj.penjualan_waktu)', $date['startDate']);
        } elseif ($date['endDate']) {
            $sql->where('DATE(pj.penjualan_waktu)', $date['endDate']);
        }
    }

    public function filterMonthYear($sql, $data) 
    {
        if ($data['month']) $sql->where(['month(pj.penjualan_waktu)'=>$data['month']]);
        if ($data['year']) $sql->where(['year(pj.penjualan_waktu)'=>$data['year']]);
    }

    public function searchData($sql, $search) 
    {
        // Jika ada params search
        if ($search != "") {
            $sql->groupStart();
            $sql->like('pj.penjualan_nota', $search, 'both');
            $sql->orLike('pj.penjualan_total', $search, 'both');
            $sql->orLike('pj.penjualan_sub_total', $search, 'both');
            $sql->orLike('pj.penjualan_keterangan', $search, 'both');
            $sql->orLike('pj.penjualan_metode_pembayaran', $search, 'both');
            $sql->orLike('pgn.pengguna_nama', $search, 'both');
            $sql->groupEnd();
        }
    }

    public function relation($sql)
    {
        $sql->join('pengguna pgn', 'pgn.pengguna_id = pj.pengguna_id', 'left');
        $sql->join('status_pembayaran stbyr', 'stbyr.status_pembayaran_id = pj.status_pembayaran_id', 'left');
        $sql->join('status_pemesanan stpem', 'stpem.status_pemesanan_id = pj.status_pemesanan_id', 'left');
    }

    public function getUniqCode($pengguna_id = 0, $date = null)
    {
        $sql = $this->db->table($this->table);
        $sql->select('RIGHT(penjualan.penjualan_nota,4) as kode_nota');
        $sql->orderBy('penjualan.penjualan_nota', 'DESC');
        $sql->limit(1);
        if ($date) {
            $sql->where(['date(penjualan.penjualan_waktu)' => $date]);
        } else {
            $sql->where(['date(penjualan.penjualan_waktu)' => date('Y-m-d')]);
        }
        $sql->where(['penjualan.pengguna_id' => $pengguna_id]);
        $query = $sql->get();
        if ($query->getNumRows() <> 0) {
            $data = $query->getRow();
            $kode = intval($data->kode_nota) + 1; 
        } else {
            $kode = 1;
        }

        $batas = str_pad($kode, 4, "0", STR_PAD_LEFT);
        $kodetampil = "PJ-" . ($date ?  $date : date('dmY-')) . $pengguna_id . '-' .$batas;
        return $kodetampil;
    }
}
