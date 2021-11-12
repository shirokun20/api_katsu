<?php

namespace App\Models;

use CodeIgniter\Model;

class PembelianModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'pembelian';
    protected $primaryKey           = 'pembelian_nota';
    protected $useAutoIncrement     = false;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'pembelian_nota',
        'pembelian_waktu',
        'pembelian_total',
        'pembelian_keterangan',
        'pengguna_id',
        'pembelian_is_valid',
        'accepted_id',
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

    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    public function relation($sql)
    {
        $sql->join('pengguna pgn', 'pgn.pengguna_id = pm.pengguna_id', 'left');
        $sql->join('pengguna ac', 'ac.pengguna_id = pm.accepted_id', 'left');
    }

    public function selectData($sql)
    {
        $sql->select('pm.pembelian_nota as pmnota');
        $sql->select('pm.pembelian_waktu as pmwaktu');
        $sql->select('pm.pembelian_total as pmtotal');
        $sql->select('pm.pembelian_keterangan as pmketerangan');
        $sql->select('pm.pengguna_id as pid');
        $sql->select('pgn.pengguna_nama as pnama');
        $sql->select('pm.pembelian_is_valid as pmisvalid');
        $sql->select('ac.pengguna_nama as acpenerima');
    }

    public function filterDate($sql, $date)
    {
        if ($date['startDate'] && $date['endDate']) {
            $sql->where('DATE(pm.pembelian_waktu) between "' . $date['startDate'] . '" and "' . $date['endDate'] . '"');
        } elseif ($date['startDate']) {
            $sql->where('DATE(pm.pembelian_waktu)', $date['startDate']);
        } elseif ($date['endDate']) {
            $sql->where('DATE(pm.pembelian_waktu)', $date['endDate']);
        }
    }

    public function filterMonthYear($sql, $data) 
    {
        if ($data['month']) $sql->where(['month(pm.pembelian_waktu)'=>$data['month']]);
        if ($data['year']) $sql->where(['year(pm.pembelian_waktu)'=>$data['year']]);
    }

    public function searchData($sql, $search = "")
    {
        if ($search != "") {
            $sql->groupStart();
            $sql->like('pm.pembelian_nota', $search, 'both');
            $sql->orLike('pm.pembelian_total', $search, 'both');
            $sql->orLike('pgn.pengguna_nama', $search, 'both');
            $sql->groupEnd();
        }
    }
    
    public function getUniqCode($pengguna_id = 0, $date = null)
    {
        $sql = $this->db->table($this->table);
        $sql->select('RIGHT(pembelian.pembelian_nota,4) as kode_nota');
        $sql->orderBy('pembelian.pembelian_nota', 'DESC');
        $sql->limit(1);
        if ($date) {
            $sql->where(['date(pembelian.pembelian_waktu)' => $date]);
        } else {
            $sql->where(['date(pembelian.pembelian_waktu)' => date('Y-m-d')]);
        }
        $sql->where(['pembelian.pengguna_id' => $pengguna_id]);
        $query = $sql->get();
        if ($query->getNumRows() <> 0) {
            $data = $query->getRow();
            $kode = intval($data->kode_nota) + 1; 
        } else {
            $kode = 1;
        }

        $batas = str_pad($kode, 4, "0", STR_PAD_LEFT);
        $kodetampil = "PB-" . ($date ?  $date : date('dmY-')) . $pengguna_id . '-' .$batas;
        return $kodetampil;
    }
}
