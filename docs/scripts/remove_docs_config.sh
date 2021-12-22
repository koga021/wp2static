#!/bin/bash
DIR="../virtual"
if [ -d "$DIR" ]; then
  echo "Python Virtual ENV exist!, deleting folder"
  rm -rf $DIR
else
  true
fi