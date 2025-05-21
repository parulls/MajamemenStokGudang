<?php
require 'function.php';
require 'cek.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Inventory Management System CV. Teknik Steel" />
    <meta name="author" content="" />
    <title>Dashboard Stock Management</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" />
    <link href="css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.1.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .low-stock { background-color: #fff3cd; }
        .out-of-stock { background-color: #f8d7da; }
    </style>
</head>
<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">CV.Teknik Steel</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
            <i class="fas fa-bars"></i>
        </button>
    </nav>
    
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <a class="nav-link active" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>
                            Stock Barang
                        </a>
                        <a class="nav-link" href="customer.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                            Data Customer
                        </a>
                        <a class="nav-link" href="supplier.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-truck"></i></div>
                            Data Supplier
                        </a>
                        <a class="nav-link" href="masuk.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-arrow-down"></i></div>
                            Barang Masuk
                        </a> 
                        <a class="nav-link" href="keluar.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-arrow-up"></i></div>
                            Barang Keluar
                        </a>
                        <a class="nav-link" href="kelolaadmin.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-user-cog"></i></div>
                            Kelola Admin
                        </a>
                        <a class="nav-link" href="logout.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>
                            Log Out
                        </a>
                    </div>
                </div>
            </nav>
        </div>
        
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">List Inventori Barang CV. Teknik Steel</h1>
                    
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                    <i class="fas fa-plus me-1"></i> Tambah Barang
                                </button>
                                <a href="export.php" class="btn btn-success ms-2">
                                    <i class="fas fa-file-export me-1"></i> Export Data
                                </a>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <?php
                            try {
                                // Display out-of-stock alerts
                                $alertQuery = $conn->prepare("SELECT idbarang, namabarang FROM stock WHERE stock < 1");
                                $alertQuery->execute();
                                
                                if ($alertQuery->rowCount() > 0) {
                                    echo '<div class="alert-container mb-4">';
                                    while($item = $alertQuery->fetch(PDO::FETCH_ASSOC)) {
                                        $barang = htmlspecialchars($item['namabarang']);
                                        $idb = htmlspecialchars($item['idbarang']);
                            ?>
                                        <div class="alert alert-danger alert-dismissible fade show">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <strong>Stok Habis!</strong> Barang <strong><?=$barang?></strong> telah habis.
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                            <?php
                                    }
                                    echo '</div>';
                                }
                            } catch (PDOException $e) {
                                echo '<div class="alert alert-warning">Gagal memeriksa stok: '.htmlspecialchars($e->getMessage()).'</div>';
                            }
                            ?>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="inventoryTable" width="100%" cellspacing="0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th width="5%">No</th>
                                            <th>Nama Barang</th>
                                            <th>Deskripsi</th>
                                            <th width="10%">Stock</th>
                                            <th width="20%">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        try {
                                            $stockQuery = $conn->prepare("SELECT * FROM stock ORDER BY namabarang");
                                            $stockQuery->execute();
                                            
                                            $i = 1;
                                            while($data = $stockQuery->fetch(PDO::FETCH_ASSOC)) {
                                                $namabarang = htmlspecialchars($data['namabarang']);
                                                $deskripsi = htmlspecialchars($data['deskripsi']);
                                                $stock = htmlspecialchars($data['stock']);
                                                $idb = htmlspecialchars($data['idbarang']);
                                                
                                                // Determine row class based on stock level
                                                $rowClass = '';
                                                if ($stock < 1) {
                                                    $rowClass = 'out-of-stock';
                                                } elseif ($stock < 5) {
                                                    $rowClass = 'low-stock';
                                                }
                                        ?>
                                        <tr class="<?=$rowClass?>">
                                            <td><?=$i++?></td>
                                            <td><?=$namabarang?></td>
                                            <td><?=$deskripsi?></td>
                                            <td class="text-center"><?=$stock?></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?=$idb?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm ms-1" data-bs-toggle="modal" data-bs-target="#deleteModal<?=$idb?>">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?=$idb?>" tabindex="-1" aria-labelledby="editModalLabel<?=$idb?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-warning text-white">
                                                        <h5 class="modal-title" id="editModalLabel<?=$idb?>">Edit Barang</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="editName<?=$idb?>" class="form-label">Nama Barang</label>
                                                                <input type="text" class="form-control" id="editName<?=$idb?>" name="namabarang" value="<?=$namabarang?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="editDesc<?=$idb?>" class="form-label">Deskripsi</label>
                                                                <input type="text" class="form-control" id="editDesc<?=$idb?>" name="deskripsi" value="<?=$deskripsi?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="editStock<?=$idb?>" class="form-label">Stock</label>
                                                                <input type="number" class="form-control" id="editStock<?=$idb?>" name="stock" value="<?=$stock?>" min="0" required>
                                                            </div>
                                                            <input type="hidden" name="idb" value="<?=$idb?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary" name="updatebarang">Simpan Perubahan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?=$idb?>" tabindex="-1" aria-labelledby="deleteModalLabel<?=$idb?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title" id="deleteModalLabel<?=$idb?>">Konfirmasi Hapus</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <p>Apakah Anda yakin ingin menghapus barang ini?</p>
                                                            <div class="alert alert-warning">
                                                                <strong><?=$namabarang?></strong><br>
                                                                Deskripsi: <?=$deskripsi?><br>
                                                                Stok: <?=$stock?>
                                                            </div>
                                                            <input type="hidden" name="idb" value="<?=$idb?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-danger" name="hapusbarang">Ya, Hapus</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr><td colspan="5" class="text-center text-danger">Gagal memuat data: '.htmlspecialchars($e->getMessage()).'</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
            
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; CV. Teknik Steel <?=date('Y')?></div>
                        <div>
                            <a href="#">Privacy Policy</a>
                            &middot;
                            <a href="#">Terms &amp; Conditions</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addItemModalLabel">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="newName" class="form-label">Nama Barang</label>
                            <input type="text" class="form-control" id="newName" name="namabarang" placeholder="Masukkan nama barang" required>
                        </div>
                        <div class="mb-3">
                            <label for="newDesc" class="form-label">Deskripsi</label>
                            <input type="text" class="form-control" id="newDesc" name="deskripsi" placeholder="Masukkan deskripsi" required>
                        </div>
                        <div class="mb-3">
                            <label for="newStock" class="form-label">Jumlah Stok</label>
                            <input type="number" class="form-control" id="newStock" name="stock" placeholder="Masukkan jumlah stok" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="addnewbarang">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="js/scripts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="assets/demo/chart-area-demo.js"></script>
    <script src="assets/demo/chart-bar-demo.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
    <script src="js/datatables-simple-demo.js"></script>
    <script>
        // Initialize DataTables
        document.addEventListener('DOMContentLoaded', function() {
            const table = new simpleDatatables.DataTable("#inventoryTable", {
                perPage: 10,
                labels: {
                    placeholder: "Cari...",
                    searchTitle: "Cari dalam tabel",
                    perPage: "entri per halaman",
                    noRows: "Data tidak ditemukan",
                    info: "Menampilkan {start} sampai {end} dari {rows} entri",
                    noResults: "Tidak ada hasil yang cocok dengan pencarian"
                }
            });
        });
    </script>
</body>
</html>