#!/bin/bash
./equal.run --do=init_db
./equal.run --do=init_package --package=core
./equal.run --do=init_package --package=identity
./equal.run --do=init_package --package=sale
./equal.run --do=init_package --package=realestate
./equal.run --do=init_package --package=finance
./equal.run --do=init_package --package=documents
./equal.run --do=init_package --package=lodging
./equal.run --do=symbiose_convert-xls
./equal.run --do=init_package --package=symbiose
