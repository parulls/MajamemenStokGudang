<?php
session_start(); 
//buat koneksi ke database
$host = 'aws-0-ap-southeast-1.pooler.supabase.com';
$user = 'postgres.lymfafguyoicqfaxjzqw';
$password = 'stokgudangkakzamzami';
$port = '5432';
$dbname = 'postgres';

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}



//input nama barang baru
if(isset($_POST['addnewbarang'])){
    $namabarang = $_POST['namabarang'];
    $deskripsi = $_POST['deskripsi'];
    $stock = $_POST['stock'];
 
    try {
        $stmt = $conn->prepare("INSERT INTO stock (namabarang, deskripsi, stock) VALUES (:namabarang, :deskripsi, :stock)");
        $stmt->bindParam(':namabarang', $namabarang);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':stock', $stock);
        $stmt->execute();
        
        header('location:index.php');
    } catch (PDOException $e) {
        echo 'Gagal: ' . $e->getMessage();
    }
}

//menambah barang masuk
if(isset($_POST['barangmasuk'])){
    $barangnya  = $_POST['barangnya'];
    $penerima   = $_POST['penerima'];
    $qty        = $_POST['qty'];   

    try {
        //check stok terkini
        $stmt = $conn->prepare("SELECT * FROM stock WHERE idbarang = :barangnya");
        $stmt->bindParam(':barangnya', $barangnya);
        $stmt->execute();
        $ambildatanya = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$ambildatanya) {
            throw new Exception("Barang tidak ditemukan");
        }

        $stocksekarang = $ambildatanya['stock'];
        $tambahkanstocksekarangdenganqty = $stocksekarang + $qty;

        $conn->beginTransaction();

        try {
            //memasukkan informasi ke table database
            $stmt1 = $conn->prepare("INSERT INTO masuk (idbarang, keterangan, qty) VALUES (:barangnya, :penerima, :qty)");
            $stmt1->bindParam(':barangnya', $barangnya);
            $stmt1->bindParam(':penerima', $penerima);
            $stmt1->bindParam(':qty', $qty);
            $stmt1->execute();

            //mengupdate stock
            $stmt2 = $conn->prepare("UPDATE stock SET stock = :new_stock WHERE idbarang = :barangnya");
            $stmt2->bindParam(':new_stock', $tambahkanstocksekarangdenganqty);
            $stmt2->bindParam(':barangnya', $barangnya);
            $stmt2->execute();

            $conn->commit();
            header('Location: masuk.php');
            exit();
        } catch (Exception $e) {
            //batalkan transaksi jika terjadi kesalahan
            $conn->rollBack();
            echo 'Gagal: ' . $e->getMessage();
            header('Location: masuk.php');
            exit();
        }
    } catch (PDOException $e) {
        echo 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

//menambah barang keluar
if(isset($_POST['barangkeluar'])){
    $barangnya  = $_POST['barangnya'];
    $penerima   = $_POST['penerima'];
    $qty        = $_POST['qty'];   

    try {
        //check stok terkini
        $stmt = $conn->prepare("SELECT * FROM stock WHERE idbarang = :barangnya");
        $stmt->bindParam(':barangnya', $barangnya);
        $stmt->execute();
        $ambildatanya = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$ambildatanya) {
            throw new Exception("Barang tidak ditemukan");
        }

        $stocksekarang = $ambildatanya['stock'];

        if($stocksekarang >= $qty){
            //jika stok mencukupi maka langsung kurangkan stok
            $kurangkanstocksekarangdenganqty = $stocksekarang - $qty;

            $conn->beginTransaction();

            try {
                $stmt1 = $conn->prepare("INSERT INTO keluar (idbarang, penerima, qty) VALUES (:barangnya, :penerima, :qty)");
                $stmt1->bindParam(':barangnya', $barangnya);
                $stmt1->bindParam(':penerima', $penerima);
                $stmt1->bindParam(':qty', $qty);
                $stmt1->execute();

                // Update stock
                $stmt2 = $conn->prepare("UPDATE stock SET stock = :new_stock WHERE idbarang = :barangnya");
                $stmt2->bindParam(':new_stock', $kurangkanstocksekarangdenganqty);
                $stmt2->bindParam(':barangnya', $barangnya);
                $stmt2->execute();

                $conn->commit();
                header('Location: keluar.php');
                exit();
            } catch (Exception $e) {
                //batalkan transaksi jika terjadi kesalahan
                $conn->rollBack();
                echo 'Gagal: ' . $e->getMessage();
                header('Location: keluar.php');
                exit();
            }
        } else {
            //jika stock tidak mencukupi
            echo '<script>
                alert("Maaf stock untuk saat ini tidak mencukupi");
                window.location.href="keluar.php";
                </script>';
        }
    } catch (PDOException $e) {
        echo 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

//update info barang
if(isset($_POST['updatebarang'])){
    $idbarang    = $_POST['idb'];
    $namabarang  = $_POST['namabarang'];
    $deskripsi   = $_POST['deskripsi'];   

    try {
        $stmt = $conn->prepare("UPDATE stock SET namabarang = :namabarang, deskripsi = :deskripsi WHERE idbarang = :idbarang");
        
        $stmt->bindParam(':namabarang', $namabarang);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':idbarang', $idbarang);
        
        $stmt->execute();
        
        if($stmt->rowCount() > 0){
            header('Location: index.php');
            exit();
        } else {
            echo '<script>alert("Tidak ada perubahan data atau barang tidak ditemukan");</script>';
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        echo 'Gagal: ' . $e->getMessage();
        error_log('Database error: ' . $e->getMessage());
        header('Location: index.php');
        exit();
    }
}

//Hapus Barang dari stock
if(isset($_POST['hapusbarang'])){
    $idbarang = $_POST['idb'];

    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("DELETE FROM stock WHERE idbarang = :idbarang");
        $stmt->bindParam(':idbarang', $idbarang);
        $stmt->execute();
        
        $conn->commit();
        header('Location: index.php');
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error deleting item: " . $e->getMessage());
        echo '<script>alert("Gagal menghapus barang");</script>';
        header('Location: index.php');
        exit();
    }
}


//Mengubah Data Barang Masuk
if(isset($_POST['updatebarangmasuk'])){
    $idbarang = $_POST['idbarang'];
    $idmasuk = $_POST['idmasuk'];
    $keterangan = $_POST['keterangan'];
    $qty = $_POST['qty'];

    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("SELECT stock FROM stock WHERE idbarang = :idbarang");
        $stmt->bindParam(':idbarang', $idbarang);
        $stmt->execute();
        $stock = $stmt->fetchColumn();
        
        $stmt = $conn->prepare("SELECT qty FROM masuk WHERE idmasuk = :idmasuk");
        $stmt->bindParam(':idmasuk', $idmasuk);
        $stmt->execute();
        $currentQty = $stmt->fetchColumn();
        
        $difference = $qty - $currentQty;
        $newStock = $stock + $difference;
        
        $stmt = $conn->prepare("UPDATE stock SET stock = :newStock WHERE idbarang = :idbarang");
        $stmt->bindParam(':newStock', $newStock);
        $stmt->bindParam(':idbarang', $idbarang);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE masuk SET qty = :qty, keterangan = :keterangan WHERE idmasuk = :idmasuk");
        $stmt->bindParam(':qty', $qty);
        $stmt->bindParam(':keterangan', $keterangan);
        $stmt->bindParam(':idmasuk', $idmasuk);
        $stmt->execute();
        
        $conn->commit();
        header('Location: masuk.php');
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error updating incoming item: " . $e->getMessage());
        echo '<script>alert("Gagal memperbarui data masuk");</script>';
        header('Location: masuk.php');
        exit();
    }
}

//menghapus barang masuk
if(isset($_POST['hapusbarangmasuk'])){
    $idbarang = $_POST['idbarang'];
    $qty = $_POST['qty'];
    $idmasuk = $_POST['idmasuk'];

    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("SELECT stock FROM stock WHERE idbarang = :idbarang");
        $stmt->bindParam(':idbarang', $idbarang);
        $stmt->execute();
        $stock = $stmt->fetchColumn();
        
        $newStock = $stock - $qty;
        
        $stmt = $conn->prepare("UPDATE stock SET stock = :newStock WHERE idbarang = :idbarang");
        $stmt->bindParam(':newStock', $newStock);
        $stmt->bindParam(':idbarang', $idbarang);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM masuk WHERE idmasuk = :idmasuk");
        $stmt->bindParam(':idmasuk', $idmasuk);
        $stmt->execute();
        
        $conn->commit();
        header('Location: masuk.php');
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error deleting incoming item: " . $e->getMessage());
        echo '<script>alert("Gagal menghapus data masuk");</script>';
        header('Location: masuk.php');
        exit();
    }
}

//Mengubah Data Barang Keluar
if(isset($_POST['updatebarangkeluar'])){
    $idbarang = $_POST['idbarang'];
    $idkeluar = $_POST['idkeluar'];
    $penerima = $_POST['penerima'];
    $qty = $_POST['qty'];

    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("SELECT stock FROM stock WHERE idbarang = :idbarang");
        $stmt->bindParam(':idbarang', $idbarang);
        $stmt->execute();
        $stockskrng = $stmt->fetchColumn();
        
        $stmt = $conn->prepare("SELECT qty FROM keluar WHERE idkeluar = :idkeluar");
        $stmt->bindParam(':idkeluar', $idkeluar);
        $stmt->execute();
        $qtyskrng = $stmt->fetchColumn();
        
        $selisih = $qty - $qtyskrng;
        $newStock = $stockskrng - $selisih;
        
        if($newStock < 0) {
            throw new Exception("Stock tidak mencukupi untuk update ini");
        }
        
        $stmt = $conn->prepare("UPDATE stock SET stock = :newStock WHERE idbarang = :idbarang");
        $stmt->bindParam(':newStock', $newStock);
        $stmt->bindParam(':idbarang', $idbarang);
        $stmt->execute();
        
        $stmt = $conn->prepare("UPDATE keluar SET qty = :qty, penerima = :penerima WHERE idkeluar = :idkeluar");
        $stmt->bindParam(':qty', $qty);
        $stmt->bindParam(':penerima', $penerima);
        $stmt->bindParam(':idkeluar', $idkeluar);
        $stmt->execute();
        
        $conn->commit();
        header('Location: keluar.php');
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error updating outgoing item: " . $e->getMessage());
        echo '<script>alert("Gagal: '.$e->getMessage().'"); window.location.href="keluar.php";</script>';
        exit();
    }
}

//menghapus barang keluar
if(isset($_POST['hapusbarangkeluar'])){
    $idbarang = $_POST['idbarang'];
    $qty = $_POST['qty'];
    $idkeluar = $_POST['idkeluar'];

    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("SELECT stock FROM stock WHERE idbarang = :idbarang");
        $stmt->bindParam(':idbarang', $idbarang);
        $stmt->execute();
        $stock = $stmt->fetchColumn();
        
        $newStock = $stock + $qty;
        
        $stmt = $conn->prepare("UPDATE stock SET stock = :newStock WHERE idbarang = :idbarang");
        $stmt->bindParam(':newStock', $newStock);
        $stmt->bindParam(':idbarang', $idbarang);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM keluar WHERE idkeluar = :idkeluar");
        $stmt->bindParam(':idkeluar', $idkeluar);
        $stmt->execute();
        
        $conn->commit();
        header('Location: keluar.php');
        exit();
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error deleting outgoing item: " . $e->getMessage());
        echo '<script>alert("Gagal menghapus data keluar"); window.location.href="keluar.php";</script>';
        exit();
    }
}

//tambah admin baru
if(isset($_POST['addnewadmin'])){
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("INSERT INTO login (email, password) VALUES (:email, :password)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->execute();
        
        header('Location: kelolaadmin.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error adding new admin: " . $e->getMessage());
        echo '<script>alert("Gagal menambahkan admin"); window.location.href="kelolaadmin.php";</script>';
        exit();
    }
}

//edit data admin
if(isset($_POST['updateadmin'])){
    $email = $_POST['emailadmin'];
    $password = $_POST['passwordbaru'];
    $idnya = $_POST['iduser'];
    
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE login SET email = :email, password = :password WHERE id_user = :id");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $idnya);
        $stmt->execute();
        
        header('Location: kelolaadmin.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error updating admin: " . $e->getMessage());
        echo '<script>alert("Gagal memperbarui admin"); window.location.href="kelolaadmin.php";</script>';
        exit();
    }
}

//hapus admin
if(isset($_POST['hapusadmin'])){
    $idnya = $_POST['iduser'];       
    
    try {
        $stmt = $conn->prepare("DELETE FROM login WHERE id_user = :id");
        $stmt->bindParam(':id', $idnya);
        $stmt->execute();
        
        header('Location: kelolaadmin.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error deleting admin: " . $e->getMessage());
        echo '<script>alert("Gagal menghapus admin"); window.location.href="kelolaadmin.php";</script>';
        exit();
    }
}

//tambah new supplier
if(isset($_POST['addnewsupplier'])){
    $namasupplier = $_POST['namasupplier'];
    $alamat = $_POST['alamat'];
    $hp = $_POST['hp'];
  
    try {
        $stmt = $conn->prepare("INSERT INTO supplier (namasupplier, alamat, hp) VALUES (:nama, :alamat, :hp)");
        $stmt->bindParam(':nama', $namasupplier);
        $stmt->bindParam(':alamat', $alamat);
        $stmt->bindParam(':hp', $hp);
        $stmt->execute();
        
        header('Location: supplier.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error adding supplier: " . $e->getMessage());
        echo '<script>alert("Gagal menambahkan supplier"); window.location.href="supplier.php";</script>';
        exit();
    }
}

//edit supplier
if(isset($_POST['updates'])){
    $namasupplier = $_POST['namasupplier'];
    $alamat = $_POST['alamat'];
    $hp = $_POST['hp'];
    $idsupp = $_POST['idsupplier'];
  
    try {
        $stmt = $conn->prepare("UPDATE supplier SET namasupplier = :nama, alamat = :alamat, hp = :hp WHERE idsupplier = :id");
        $stmt->bindParam(':nama', $namasupplier);
        $stmt->bindParam(':alamat', $alamat);
        $stmt->bindParam(':hp', $hp);
        $stmt->bindParam(':id', $idsupp);
        $stmt->execute();
        
        header('Location: supplier.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error updating supplier: " . $e->getMessage());
        echo '<script>alert("Gagal memperbarui supplier"); window.location.href="supplier.php";</script>';
        exit();
    }
}
    
//delete supplier
if(isset($_POST['hapussupplier'])){
    $idsupp = $_POST['idsupplier'];       
    
    try {
        $stmt = $conn->prepare("DELETE FROM supplier WHERE idsupplier = :id");
        $stmt->bindParam(':id', $idsupp);
        $stmt->execute();
        
        header('Location: supplier.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error deleting supplier: " . $e->getMessage());
        echo '<script>alert("Gagal menghapus supplier"); window.location.href="supplier.php";</script>';
        exit();
    }
}

//add new customer
if(isset($_POST['addnewcustomer'])){
    $namacustomer = $_POST['namacustomer'];
    $alamat = $_POST['alamat'];
    $hp = $_POST['hp'];
  
    try {
        $stmt = $conn->prepare("INSERT INTO customer (namacustomer, alamat, hp) VALUES (:nama, :alamat, :hp)");
        $stmt->bindParam(':nama', $namacustomer);
        $stmt->bindParam(':alamat', $alamat);
        $stmt->bindParam(':hp', $hp);
        $stmt->execute();
        
        header('Location: customer.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error adding customer: " . $e->getMessage());
        echo '<script>alert("Gagal menambahkan customer"); window.location.href="customer.php";</script>';
        exit();
    }
}

//update customer
if(isset($_POST['updates'])){
    $namacustomer = $_POST['namacustomer'];
    $alamat = $_POST['alamat'];
    $hp = $_POST['hp'];
    $idcust = $_POST['idcust'];
  
    try {
        $stmt = $conn->prepare("UPDATE customer SET namacustomer = :nama, alamat = :alamat, hp = :hp WHERE idcustomer = :id");
        $stmt->bindParam(':nama', $namacustomer);
        $stmt->bindParam(':alamat', $alamat);
        $stmt->bindParam(':hp', $hp);
        $stmt->bindParam(':id', $idcust);
        $stmt->execute();
        
        header('Location: customer.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error updating customer: " . $e->getMessage());
        echo '<script>alert("Gagal memperbarui customer"); window.location.href="customer.php";</script>';
        exit();
    }
}

//delete customer
if(isset($_POST['hapuscustomer'])){
    $idcust = $_POST['idcust'];       
    
    try {
        $stmt = $conn->prepare("DELETE FROM customer WHERE idcustomer = :id");
        $stmt->bindParam(':id', $idcust);
        $stmt->execute();
        
        header('Location: customer.php');
        exit();
    } catch (PDOException $e) {
        error_log("Error deleting customer: " . $e->getMessage());
        echo '<script>alert("Gagal menghapus customer"); window.location.href="customer.php";</script>';
        exit();
    }
}
?>
