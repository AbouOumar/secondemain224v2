#!/bin/bash
# Simple curl request to check homepage
curl -s http://127.0.0.1:8000/ | grep -c 'article-item'
