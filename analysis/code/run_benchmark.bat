@echo off
echo =====================================
echo   BENCHMARK INTERAKSI OBAT - START
echo =====================================

echo.
echo [PHP] Menjalankan interaction_checker.php...
php interaction_checker.php

echo.
echo [PYTHON] Menjalankan cek_interaksi.py...
python cek_interaksi.py

echo.
echo [PYTHON] Menjalankan nested_dictionary_cek_interaksi.py...
python nested_dictionary_cek_interaksi.py

echo.
echo [PYTHON] Menjalankan simple_dictionary_cek_interkasi.py...
python simple_dictionary_cek_interkasi.py

echo.
echo [PYTHON] Menjalankan multiindex_pandas_filebased.py...
python multiindex_pandas_filebased.py

echo.
echo [PHP] Menjalankan cek_interaksi_db_benchmark.php...
php cek_interaksi_db_benchmark.php


echo.
echo [REKAP] Menjalankan rekap_benchmark.py...
python rekap_benchmark.py

echo.
echo =====================================
echo         BENCHMARK SELESAI
echo =====================================
pause
