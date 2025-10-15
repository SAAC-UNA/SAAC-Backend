@echo off
echo Cambiando a configuracion Docker...
copy .env .env.xampp
copy .env.docker .env
echo Configuracion Docker activada!
echo Usa: docker-compose up -d
pause