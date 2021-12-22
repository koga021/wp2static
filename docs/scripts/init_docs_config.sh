#!/bin/bash
DIR="../virtual"
if [ -d "$DIR" ]; then
  echo "Python Virtual ENV exist!"
else
  ###  Control will jump here if $DIR does NOT exists ###
  echo "Creating Python Virtual ENV"
  python3 -m venv ../virtual
fi