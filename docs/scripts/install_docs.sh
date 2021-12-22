#!/bin/bash
DIR="../virtual"
if [ -d "$DIR" ]; then
  echo "Python Virtual ENV exist!"
  source $DIR/bin/activate
  pip install --upgrade pip
  pip install -r ../requirements.txt
else
  ###  Control will jump here if $DIR does NOT exists ###
  echo "Creating Python Virtual ENV"
  #python3 -m venv ../virtual
fi