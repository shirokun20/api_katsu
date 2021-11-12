<?php

namespace App\Models;

use CodeIgniter\Model;

class PenDetailModel extends Model
{
    protected $DBGroup              = 'default';
    protected $table                = 'detail_penjualan';
    protected $primaryKey           = 'detail_penjualan_id';
    protected $useAutoIncrement     = true;
    protected $insertID             = 0;
    protected $returnType           = 'array';
    protected $useSoftDeletes       = false;
    protected $protectFields        = true;
    protected $allowedFields        = [
        'detail_penjualan_id',
        'produk_kode',
        'penjualan_nota',
        'detail_penjualan_qty',
        'detail_penjualan_harga',
        'detail_penjualan_total',
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

    protected function _relation($sql)
    {
        $sql->join('produk prod', 'prod.produk_kode = dp.produk_kode', 'left');
    }

    protected function _select($sql)
    {
        $sql->select('dp.detail_penjualan_id as dpjid');
        $sql->select('dp.produk_kode as dpjpk');
        $sql->select('dp.detail_penjualan_qty as dpjpq');
        $sql->select('dp.detail_penjualan_harga as dpjph');
        $sql->select('dp.detail_penjualan_total as dpjpt');
        $sql->select('prod.produk_nama as pdknm');
    }

    public function getData($where = null) 
    {
        $sql = $this->db->table($this->table. ' dp');
        $this->_relation($sql);
        $this->_select($sql);
        $sql->where($where);
        return $sql->get();
    }
}
