FROM nginx:1.27-alpine

# Hapus konfigurasi default bawaan nginx
RUN rm /etc/nginx/conf.d/default.conf

# Pasang konfigurasi nginx project
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Salin aset dist yang baru di-build langsung ke web root
COPY dist /usr/share/nginx/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]