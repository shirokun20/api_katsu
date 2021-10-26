<?php

namespace App\Models;

use CodeIgniter\Model;

class JurnalModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'jurnal';
    protected $primaryKey           = 'jurnal_kode';
    protected $useAutoIncrement     = false;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'jurnal_kode',
        'jurnal_waktu',
        'jurnal_ref', 
        'jurnal_debet',
        'jurnal_kredit',
        'jurnal_saldo',
        'jurnal_keterangan'
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

    private function getNewLastData()
    {
        $sql = $this->db->table($this->table);
        $sql->orderBy('jurnal.jurnal_waktu', 'DESC');
        $sql->limit(1);
        $result = $sql->get();
        return $result->getRow();
    }
    
    public function getUniqCode($kode = 4)
    {
        $sql = $this->db->table($this->table);
        $sql->select('RIGHT(jurnal.jurnal_kode,4) as jurnal_kode');
        $sql->orderBy('jurnal.jurnal_kode', 'DESC');
        $sql->limit(1);
        $sql->where(['month(jurnal.jurnal_waktu)' => date('m')]);
        $sql->where(['year(jurnal.jurnal_waktu)' => date('Y')]);
        $query = $sql->get();
        if ($query->getNumRows() <> 0) {
            $data = $query->getRow();
            $kode = intval($data->jurnal_kode) + 1; 
        } else {
            $kode = 1;
        }

        $batas = str_pad($kode, 4, "0", STR_PAD_LEFT);
        $kodetampil =  $kode . date('dmY-') . '-' .$batas;
        return $kodetampil;
    }

}
