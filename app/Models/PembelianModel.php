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
    
    public function getUniqCode($pengguna_id = 0, $date = null)
    {
        $sql = $this->db->table($this->table);
        $sql->select('RIGHT(pembelian.pembelian_nota,4) as kode_nota');
        $sql->orderBy('pembelian.pembelian_nota', 'DESC');
        $sql->limit(1);
        if ($date != null) {
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
        $kodetampil = "PB-" . ($date != null ?  $date : date('dmY-')) . $pengguna_id . '-' .$batas;
        return $kodetampil;
    }
}
