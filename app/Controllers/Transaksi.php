<?php

namespace App\Controllers;

use App\Models\BarangModel;
use App\Models\TransaksiModel;
use App\Models\UserModel;
use App\Models\DiskonModel;
use App\Libraries\Mypdf;

class Transaksi extends BaseController
{ 
    public function __construct()
	{ 
        helper('form');
		$this->validation = \Config\Services::validation();
		$this->transaksi = new TransaksiModel();
		$this->barang = new BarangModel();
		$this->pembeli = new UserModel();
		$this->diskon = new DiskonModel();
		$this->pdf = new Mypdf();

	}

    public function index()
	{
		$id = session('id');
		$transaksi = $this->transaksi->where('id_pembeli', $id)->findAll();  
		return view('transaksi/index',[
			'transaksis' => $transaksi,  
		]);
	}

    public function buy()
    { 
        if($this->request->getPost())
		{
			$data = $this->request->getPost();
			$this->validation->run($data, 'transaksi');
			$errors = $this->validation->getErrors();

			if(!$errors){
				$transaksi = new \App\Entities\Transaksi();

				$id_barang = $this->request->getPost('id_barang');
				$jumlah_pembelian = $this->request->getPost('jumlah');
				$total_harga = $this->request->getPost('total_harga');
				$kodeVoucher = $this->request->getPost('kodeVoucher');
				$dataVoc = $this->diskon->where('kode_voucher', $kodeVoucher)->first();

				$barang = $this->barang->find($id_barang);
				$entityBarang = new \App\Entities\Barang();
				
				$entityBarang->id = $id_barang;

				$entityBarang->stok = $barang->stok-$jumlah_pembelian;
				$this->barang->save($entityBarang);

				$transaksi->fill($data);
				$transaksi->status = 0;
				$transaksi->created_by = session('id');
				$transaksi->created_date = date("Y-m-d H:i:s");

				// $this->transaksi->save($transaksi);

				// $id = $this->transaksi->insertID(); 
				// echo $kodeVoucher;
				if($dataVoc == ""){
					return "Voucher tidak ada";
				} else {
					if($dataVoc->aktif == 1){
						if(strtotime($dataVoc->tanggal_mulai_berlaku) > strtotime(date("Y-m-d"))){
							return "Promo belum berlaku";
						}
	
						if(strtotime($dataVoc->tanggal_akhir_berlaku) < strtotime(date("Y-m-d"))){
							return "Promo sudah berakhir";
						}
						$potongan = $total_harga*$dataVoc->besar_diskon/100;
	
						return "Total Harga Barang: $total_harga <br /> Diskon: ".$dataVoc->besar_diskon."% <br /> Potongan: ".$potongan."<br /> Harga setelah Diskon: ".$total_harga-$potongan;
					} else {
						return "Promo tidak aktif";
					}
				}
				// return $kodeVoucher;

				// return redirect()->to('transaction');
			}
		}
    }
 
    public function invoice(){
		$id = $this->request->uri->getSegment(2);
		$transaksi = $this->transaksi->find($id);

		$userModel = new \App\Models\UserModel();
		$pembeli = $userModel->find($transaksi->id_pembeli);

		$barang = $this->barang->find($transaksi->id_barang);
		$data = [
			'transaksi'=> $transaksi,
			'pembeli' => $pembeli,
			'barang' => $barang,
		];
 
        $filename = date('y-m-d-H-i-s'). '-invoice';
		$this->pdf->generate('transaksi/invoice',$data,$filename);
    }
}