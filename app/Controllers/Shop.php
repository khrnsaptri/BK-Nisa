<?php

namespace App\Controllers;
use App\Models\BarangModel;
use App\Models\KategoriModel;
use App\Models\KomentarModel;
use App\Models\DiskonModel;
use App\Libraries\Bantuan;

class Shop extends BaseController
{
    private $url = "https://api.rajaongkir.com/starter/";
	private $apiKey = "645e2a795033b43bb13404ea9deb993a";

    public function __construct()
	{ 
        helper('form'); 
		$this->kategori = new KategoriModel();
        $this->barang = new BarangModel();
        $this->komentar = new KomentarModel();
        $this->diskon = new DiskonModel();
        $this->bantuan = new Bantuan();
	}

	public function index()
	{
		$barang = $this->barang->select('barang.*, kategori.nama AS kategori')->join('kategori', 'barang.id_kategori=kategori.id')->findAll();
        $kategori = $this->kategori->findAll();
		return view('shop/index',[
			'barangs' => $barang,
            'kategoris' => $kategori,
		]);
	}

    public function category()
	{
		$id = $this->request->uri->getSegment(3);


		$barang = $this->barang->select('barang.*, kategori.nama AS kategori')->where('id_kategori', $id)->join('kategori', 'barang.id_kategori=kategori.id')->where('id_kategori', $id)->findAll(); 
        $kategori = $kategoriModel->findAll();
		return view('shop/index',[
			'barangs' => $barang, 
            'kategoris' => $kategori,
		]);
	} 

    public function product()
	{
		$id = $this->request->uri->getSegment(3);

        $barang = $this->barang->find($id); 
        $kategori = $this->kategori->findAll();
        $komentar = $this->komentar->select('komentar.*, user.username')->where('id_barang', $id)->join('user', 'komentar.id_user=user.id')->findAll();

        $provinsi = $this->bantuan->rajaongkir($this->url."province",$this->apiKey,method:"GET");
        
        return view('shop/product',[
            'barang' => $barang, 
            'kategoris' => $kategori,
            'komentars' => $komentar,
            'provinsi'=> json_decode($provinsi)->rajaongkir->results,
        ]);
	}
    
    public function getCity()
    {
        if ($this->request->isAJAX()){
            $id_province = $this->request->getGet('id_province');
            $send = ['province'=>$id_province];
            $data = $this->bantuan->rajaongkir($this->url."city?province=".$id_province,$this->apiKey,method:"GET");
            return $this->response->setJSON($data);
        }
    }

    public function getCost()
    {
        if ($this->request->isAJAX()){
            $origin = $this->request->getGet('origin');
            $destination = $this->request->getGet('destination');
            $weight = $this->request->getGet('weight');
            $courier = $this->request->getGet('courier');
            $send = [
                'origin' => $origin,
                'destination' => $destination,
                'weight' => $weight,
                'courier' => $courier,
            ];
            $data = $this->bantuan->rajaongkir($this->url."cost", $this->apiKey, $send, "POST");
            return $this->response->setJSON($data);
        }
    }

    public function checkVoucher(){
        if($this->request->isAJAX()){
            $voc = $this->request->getPost('voucher');
            $dataVoc = $this->diskon->where('kode_voucher', $voc)->first();

            if($dataVoc->aktif == 1){
                if(strtotime($dataVoc->tanggal_mulai_berlaku) > strtotime(date("Y-m-d"))){
                    $data = [
                        'success' => false,
                        'msg'     => 'Promo belum berlaku'
                    ];
                    return $this->response->setJSON($data);
                }

                if(strtotime($dataVoc->tanggal_akhir_berlaku) > strtotime(date("Y-m-d"))){
                    $data = [
                        'success' => false,
                        'msg'     => 'Promo belum sudah berakhir'
                    ];
                    return $this->response->setJSON($data);
                }

                $data = [
                    'success'       => true,
                    'kode_voucher'  => $dataVoc->kode_voucher,
                    'diskon'        => $dataVoc->besar_diskon
                ];
            } else {
                $data = [
                    'success' => false,
                    'msg'     => 'Promo kode tidak aktif'
                ];
            }
            return $this->response->setJSON($data);
        }
    }
}