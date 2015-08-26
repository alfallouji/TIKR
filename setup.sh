#!/bin/bash
SCRIPT_FOLDER=`dirname "$0"`
cd $SCRIPT_FOLDER
CURRENT_FOLDER=`pwd`

echo $CURRENT_FOLDER
cd $CURRENT_FOLDER

echo "Checking pre-requisite package"
sudo apt-get install unzip openjdk-7-jre 

echo "Creating folders"
mkdir apps/
cd apps/

mkdir tika
cd tika
wget http://apache.uberglobalmirror.com/tika/tika-app-1.10.jar
mv tika-app-1.10.jar tika-app.jar

cd ./../
wget http://apache.uberglobalmirror.com/lucene/solr/5.3.0/solr-5.3.0.zip
unzip solr-5.3.0.zip
mv solr-5.3.0 solr
rm solr-5.3.0.zip

./solr/bin/solr start
./solr/bin/solr create_core -c origin
./solr/bin/solr stop
ln -s $CURRENT_FOLDER/conf/solr/managed-schema $CURRENT_FOLDER/apps/solr/server/solr/origin/conf/managed-schema
./solr/bin/solr start

cd $CURRENT_FOLDER
