# Description Quality Report

This report verifies the quality of transaction descriptions processed by the updated Vfos pipeline.

| Message ID | Template | Extracted Merchant / Counterparty | Final Description | PASS / FAIL | Notes |
| --- | --- | --- | --- | --- | --- |
| `19fd23d5...` | `BCA_QRIS` | RANAH MINANG PORONG | **QRIS Ranah Minang Porong** | PASS |  |
| `19fc6369...` | `MANDIRI_TOPUP` | ShopeePay | **Top Up ShopeePay** | PASS | Successfully normalized ShopeePay topup |
| `19fc6148...` | `BCA_QRIS` | warung Manado | **QRIS warung Manado** | PASS |  |
| `19fc55a6...` | `BCA_VIRTUAL_ACCOUNT` | by.U / SuperKaget3GB14 | **by.U SuperKaget3GB14** | PASS | Successfully normalized by.U and preserved product name |
| `19fc0e22...` | `BCA_CASH_DEPOSIT` | MOH AFINI FATHURROZI | **Cash Deposit** | PASS |  |
| `19fc0e14...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19fc016b...` | `BCA_TRANSFER` | INAYAH TOYIBAH / INAYAH TOYIBAH | **Kost agustus** | PASS |  |
| `19fc0165...` | `MANDIRI_BIFAST` | MOH AFINI FATHURROZI / MOH AFINI FATHURROZI | **Transfer Internal** | PASS | Correctly classified internal own-account transfer |
| `19fbda7f...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19fbbda2...` | `BCA_QRIS` | warung Manado | **QRIS warung Manado** | PASS |  |
| `19fbaeb9...` | `MANDIRI_TRANSFER` | HERLINA WIJAYANTI / HERLINA WIJAYANTI | **Transfer to Herlina Wijayanti** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19fbae9b...` | `MANDIRI_TRANSFER` | DWI SASTRI ARTIKAH / DWI SASTRI ARTIKAH | **Transfer to Dwi Sastri Artikah** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19fb69e5...` | `BCA_QRIS` | Wr sri wuning | **QRIS Wr sri wuning** | PASS |  |
| `19fb660a...` | `MANDIRI_TRANSFER` | MUHAMMAD FAJAR ARZAK / MUHAMMAD FAJAR ARZAK | **Transfer to Muhammad Fajar Arzak** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19fb2a73...` | `MANDIRI_TRANSFER` | AGUS PURWASIH / AGUS PURWASIH | **Transfer to Agus Purwasih** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19fac517...` | `BCA_QRIS` | warung Manado | **QRIS warung Manado** | PASS |  |
| `19fab962...` | `MANDIRI_TRANSFER` | ULIL ALIFIANIN / ULIL ALIFIANIN | **Transfer to Ulil Alifianin** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19fab6aa...` | `BCA_QRIS` | JUALAN IBUK | **QRIS Jualan Ibuk** | PASS |  |
| `19fa6426...` | `BCA_QRIS` | JUALAN IBUK | **QRIS Jualan Ibuk** | PASS |  |
| `19fa207f...` | `BCA_QRIS` | warung Manado | **QRIS warung Manado** | PASS |  |
| `19f9f42e...` | `BCA_TRANSFER` | MOHAMMAD GOFIR / MOHAMMAD GOFIR | **Transfer to Mohammad Gofir** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19f9e5b6...` | `BCA_QRIS` | BISKU | **QRIS Bisku** | PASS |  |
| `19f9df3a...` | `MANDIRI_TOPUP` | OVO | **Top Up OVO** | PASS |  |
| `19f9df27...` | `MANDIRI_TOPUP` | GoPay Customer | **Top Up GoPay** | PASS |  |
| `19f9c359...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19f98e7b...` | `BCA_VIRTUAL_ACCOUNT` | NUSA SATU INTI ARTHA PT / DOKU AGODA / Indonesia | **Agoda Indonesia** | PASS |  |
| `19f94801...` | `BCA_QRIS` | KEDAI BUMBU ARB | **QRIS Kedai Bumbu Arb** | PASS |  |
| `19f943bf...` | `BCA_QRIS` | nimz cake | **QRIS Nimz Cake** | PASS |  |
| `19f92919...` | `BCA_QRIS` | Wr sri wuning | **QRIS Wr sri wuning** | PASS |  |
| `19f8c81a...` | `BCA_QRIS` | LAPAK PEJUANG UMKM | **QRIS Lapak Pejuang Umkm** | PASS |  |
| `19f8a2df...` | `MANDIRI_TRANSFER` | ULIL ALIFIANIN / ULIL ALIFIANIN | **Transfer to Ulil Alifianin** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19f884b4...` | `BCA_QRIS` | PECELMAT | **QRIS Pecelmat** | PASS |  |
| `19f875bd...` | `BCA_QRIS` | JUALAN IBUK | **QRIS Jualan Ibuk** | PASS |  |
| `19f84ef0...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19f84c48...` | `BCA_TRANSFER` | DIMAS ERRICO PUTRA HUSAD / DIMAS ERRICO PUTRA HUSAD | **Transfer to Dimas Errico Putra Husad** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19f832ee...` | `BCA_QRIS` | warung Manado | **QRIS warung Manado** | PASS |  |
| `19f7f5d4...` | `BCA_QRIS` | Mie Daily Ngoro | **QRIS Mie Daily Ngoro** | PASS |  |
| `19f77fed...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19f77fdf...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19f77e10...` | `MANDIRI_BIFAST` | MOH AFINI FATHURROZI / MOH AFINI FATHURROZI | **Transfer Internal** | PASS | Correctly classified internal own-account transfer |
| `19f73ec3...` | `BCA_VIRTUAL_ACCOUNT` | by.U / SuperKaget3GB14 | **by.U SuperKaget3GB14** | PASS | Successfully normalized by.U and preserved product name |
| `19f7030c...` | `BCA_TRANSFER` | INAYAH TOYIBAH / INAYAH TOYIBAH | **Galon** | PASS |  |
| `19f6e863...` | `BCA_QRIS` | NASI PECEL SELERA PEDAS | **QRIS Nasi Pecel Selera Pedas** | PASS |  |
| `19f6ae6f...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19f6452e...` | `MANDIRI_TOPUP` | ShopeePay | **Top Up ShopeePay** | PASS | Successfully normalized ShopeePay topup |
| `19f5e292...` | `BCA_QRIS` | JUALAN IBUK | **QRIS Jualan Ibuk** | PASS |  |
| `19f5b7d5...` | `BCA_QRIS` | PT Tokopedia | **QRIS Tokopedia** | PASS |  |
| `19f5b5fd...` | `BCA_QRIS` | WASHPOINT LAUNDRY NGORO | **QRIS Washpoint Laundry Ngoro** | PASS |  |
| `19f5693f...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19f56750...` | `BCA_QRIS` | TERAS LARISSO | **QRIS Teras Larisso** | PASS |  |
| `19f563f4...` | `MANDIRI_TOPUP` | GoPay Customer | **Top Up GoPay** | PASS |  |
| `19f55bd8...` | `BCA_QRIS` | CONATO BAKERY AMBULU | **QRIS Conato Bakery Ambulu** | PASS |  |
| `19f54c77...` | `BCA_QRIS` | PT Tokopedia | **QRIS Tokopedia** | PASS |  |
| `19f519aa...` | `MANDIRI_QRIS` | SS LIPPO SIDOARJO SSDBKab. Sidoarjo - ID / SS LIPPO SIDOARJO SSDBKab. Sidoarjo - ID | **QRIS SS LIPPO SIDOARJO SSDBKab. Sidoarjo - ID** | PASS |  |
| `19f51138...` | `MANDIRI_TRANSFER` | RANIDYA PUTRI ERIDHA / RANIDYA PUTRI ERIDHA | **Transfer to Ranidya Putri Eridha** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19f50df9...` | `BCA_QRIS` | nimz cake | **QRIS Nimz Cake** | PASS |  |
| `19f4ee2b...` | `BCA_TRANSFER` | HAZBI ASSHIDDIQI / HAZBI ASSHIDDIQI | **Transfer to Hazbi Asshiddiqi** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19f4c3c8...` | `BCA_QRIS` | Kedai SegoPecelBlitar1 | **QRIS Kedai SegoPecelBlitar1** | PASS |  |
| `19f4a7c5...` | `BCA_QRIS` | IDM INDOMARET D | **QRIS Idm Indomaret D** | PASS |  |
| `19f41da8...` | `BCA_QRIS` | RIANG RIA-HO | **QRIS Riang Ria-ho** | PASS |  |
| `19f41d2f...` | `MANDIRI_TRANSFER` | MOH. AFINI FATHUR RO / MOH. AFINI FATHUR RO | **Transfer Internal** | PASS | Correctly classified internal own-account transfer |
| `19f3c8a0...` | `BCA_QRIS` | ALFAMART REGULAR D | **QRIS Alfamart Regular D** | PASS |  |
| `19f3209d...` | `BCA_QRIS` | SEGO SAMBEL MAK YEYE | **QRIS Sego Sambel Mak Yeye** | PASS |  |
| `19f3094a...` | `BCA_QRIS` | COSH SPACE-HO | **QRIS Cosh Space-ho** | PASS |  |
| `19f3069a...` | `BCA_QRIS` | Rumah makan koki kita | **QRIS Rumah makan koki kita** | PASS |  |
| `19f2cdaf...` | `BCA_VIRTUAL_ACCOUNT` | NUSA SATU INTI ARTHA PT / DOKU AGODA / Indonesia | **Agoda Indonesia** | PASS |  |
| `19f2af0e...` | `BCA_QRIS` | IMAM BUKHORI, Pulsa &amp; Int | **QRIS IMAM BUKHORI, Pulsa &amp; Int** | PASS |  |
| `19f23774...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19f2363e...` | `BCA_QRIS` | KEDAI BUMBU ARB | **QRIS Kedai Bumbu Arb** | PASS |  |
| `19f20517...` | `BCA_TRANSFER` | INAYAH TOYIBAH / INAYAH TOYIBAH | **Kost juli** | PASS |  |
| `19f0c405...` | `BCA_QRIS` | KOPI CALF TUNJUNGAN SURAB | **QRIS Kopi Calf Tunjungan Surab** | PASS |  |
| `19f0b9df...` | `MANDIRI_TOPUP` | ShopeePay | **Top Up ShopeePay** | PASS | Successfully normalized ShopeePay topup |
| `19f09922...` | `BCA_QRIS` | SEGO SAMBEL MAK YEYE | **QRIS Sego Sambel Mak Yeye** | PASS |  |
| `19f095cc...` | `BCA_QRIS` | AYYASHI STORE | **QRIS Ayyashi Store** | PASS |  |
| `19f09596...` | `BCA_QRIS` | SARA KARTIKA A | **QRIS Sara Kartika A** | PASS |  |
| `19f08d57...` | `BCA_VIRTUAL_ACCOUNT` | NUSA SATU INTI ARTHA PT / DOKU AGODA / Indonesia | **Agoda Indonesia** | PASS |  |
| `19f01764...` | `BCA_QRIS` | LAPAK PEJUANG UMKM | **QRIS Lapak Pejuang Umkm** | PASS |  |
| `19efc50a...` | `BCA_QRIS` | LAPAK PEJUANG UMKM | **QRIS Lapak Pejuang Umkm** | PASS |  |
| `19ef1ece...` | `MANDIRI_TRANSFER` | ULIL ALIFIANIN / ULIL ALIFIANIN | **Transfer to Ulil Alifianin** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19eef580...` | `BCA_QRIS` | purnama ngoro | **QRIS Purnama Ngoro** | PASS |  |
| `19eeeac6...` | `MANDIRI_TRANSFER` | ANGGRAENI FRISKA ELI / ANGGRAENI FRISKA ELI | **Transfer to Anggraeni Friska Eli** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19eea120...` | `BCA_QRIS` | KEDAI HOMY PUNGGING | **QRIS Kedai Homy Pungging** | PASS |  |
| `19ee7ff3...` | `BCA_TRANSFER` | DANNY HARIYANTO / DANNY HARIYANTO | **Transfer to Danny Hariyanto** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19ee7b70...` | `BCA_QRIS` | JUALAN IBUK | **QRIS Jualan Ibuk** | PASS |  |
| `19ee464a...` | `MANDIRI_TRANSFER` | MOH. AFINI FATHUR RO / MOH. AFINI FATHUR RO | **Transfer Internal** | PASS | Correctly classified internal own-account transfer |
| `19ee02ed...` | `BCA_QRIS` | MIE BLEDEK | **QRIS Mie Bledek** | PASS |  |
| `19edb677...` | `BCA_QRIS` | PAWITRA COFFEE | **QRIS Pawitra Coffee** | PASS |  |
| `19eda09a...` | `BCA_QRIS` | IOH | **QRIS Indosat** | PASS |  |
| `19ed5d4a...` | `BCA_TRANSFER` | FAJAR / FAJAR | **Transfer to Fajar** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19ed5c98...` | `BCA_TRANSFER` | SITI FATIMAH / SITI FATIMAH | **Transfer to Siti Fatimah** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19ed5be6...` | `BCA_QRIS` | YOULTH COFFEE NGORO MJK | **QRIS Youlth Coffee Ngoro Mjk** | PASS |  |
| `19ec37ab...` | `BCA_CASH_WITHDRAW` | - | **Cash Withdrawal** | PASS |  |
| `19ec0ffe...` | `BCA_QRIS` | ESB RESTAURANT TECH - QRI | **QRIS Esb Restaurant Tech - Qri** | PASS |  |
| `19ec0bfc...` | `BCA_QRIS` | nimz cake | **QRIS Nimz Cake** | PASS |  |
| `19ebc7ba...` | `BCA_TRANSFER` | INAYAH TOYIBAH / INAYAH TOYIBAH | **Transfer to Inayah Toyibah** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19ebb95e...` | `BCA_CASH_DEPOSIT` | MOH AFINI FATHURROZI | **Cash Deposit** | PASS |  |
| `19eba4c3...` | `BCA_QRIS` | Wr sri wuning | **QRIS Wr sri wuning** | PASS |  |
| `19eb96fd...` | `MANDIRI_BIFAST` | ANITA NINIK YANUARI / ANITA NINIK YANUARI | **Transfer to Anita Ninik Yanuari** | PASS | Successfully formatted transfer without trailing bank or account noise |
| `19eb5347...` | `BCA_QRIS` | IDM INDOMARET D | **QRIS Idm Indomaret D** | PASS |  |
