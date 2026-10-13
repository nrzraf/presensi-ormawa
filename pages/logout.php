<?php
logout();
flash('info', 'Anda telah keluar dari sistem.');
redirect('index.php?r=login');
