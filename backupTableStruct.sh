#!/bin/bash

mysqldump -h 192.168.1.198 -uroot -p123456  banling_farm  > ./sql/banling.sql
