#!/bin/env bash
docker build -t laravel-images-test . \
  && docker run --rm laravel-images-test
