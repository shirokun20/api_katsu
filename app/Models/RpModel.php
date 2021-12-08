<?php

namespace App\Models;

use CodeIgniter\Model;

class RpModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'restok_produk';
    protected $primaryKey           = 'restok_produk_id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'restok_produk_id', 
        'restok_produk_waktu', 
        'restok_produk_jumlah', 
        'restok_produk_is_valid',
        'produk_kode',
        'pengguna_id'
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


    public function filterDate($sql, $date)
    {
        if ($date['startDate'] && $date['endDate']) {
            $sql->where('DATE(rp.restok_produk_waktu) between "' . $date['startDate'] . '" and "' . $date['endDate'] . '"');
        } elseif ($date['startDate']) {
            $sql->where('DATE(rp.restok_produk_waktu)', $date['startDate']);
        } elseif ($date['endDate']) {
            $sql->where('DATE(rp.restok_produk_waktu)', $date['endDate']);
        }
    }


}
