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
        'jurnal_ref_kode', 
        'jurnal_debet',
        'jurnal_kredit',
        'jurnal_keterangan',
        'rekening_kode',
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

    public function filterDate($sql, $date)
    {
        if ($date['startDate'] && $date['endDate']) {
            $sql->where('DATE(j.jurnal_waktu) between "' . $date['startDate'] . '" and "' . $date['endDate'] . '"');
        } elseif ($date['startDate']) {
            $sql->where('DATE(j.jurnal_waktu)', $date['startDate']);
        } elseif ($date['endDate']) {
            $sql->where('DATE(j.jurnal_waktu)', $date['endDate']);
        }
    }
    
    public function filterMonthYear($sql, $data) 
    {
        if ($data['month']) $sql->where(['month(j.jurnal_waktu)'=>$data['month']]);
        if ($data['year']) $sql->where(['year(j.jurnal_waktu)'=>$data['year']]);
    }

    public function selectData($sql)
    {
        $sql->select('jurnal_kode as jurkode');
        $sql->select("date(j.jurnal_waktu) as jurtgl");
        $sql->select("j.jurnal_ref_kode as jurref");
        $sql->select("j.jurnal_debet as jurdebet");
        $sql->select("j.jurnal_kredit as jurkredit");
        $sql->select("j.jurnal_keterangan as jurket");
        // $sql->select("month(j.jurnal_waktu) as jurbln");
        // $sql->select("month(j.jurnal_waktu) as jurbln");
    }

    private function getNewLastData()
    {
        $sql = $this->db->table($this->table);
        $sql->orderBy('jurnal.jurnal_waktu', 'DESC');
        $sql->limit(1);
        $result = $sql->get();
        return $result->getRow();
    }
    
    public function getUniqCode($kdData = 4)
    {
        $sql = $this->db->table($this->table);
        $sql->select('RIGHT(jurnal.jurnal_kode,4) as kode_jurnal');
        $sql->orderBy('jurnal.jurnal_kode', 'DESC');
        $sql->limit(1);
        $sql->where(['month(jurnal.jurnal_waktu)' => date('m')]);
        $sql->where(['year(jurnal.jurnal_waktu)' => date('Y')]);
        $sql->where(['jurnal.rekening_kode' => $kdData]);
        $query = $sql->get();
        if ($query->getNumRows() <> 0) {
            $data = $query->getRow();
            $kode = intval($data->kode_jurnal) + 1; 
        } else {
            $kode = 1;
        }

        $batas = str_pad($kode, 4, "0", STR_PAD_LEFT);
        $kodetampil =  "JU-" . $kdData .'-' . date('mY-') . $batas;
        return $kodetampil;
    }

}
