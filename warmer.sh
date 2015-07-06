#!/bin/bash
php shell/warmer.php price ASC &
php shell/warmer.php price DESC &
php shell/warmer.php id ASC &
php shell/warmer.php id DESC &

