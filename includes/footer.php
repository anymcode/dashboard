            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Simple active state script
        const currentPath = window.location.pathname;
        const links = document.querySelectorAll('.sidebar-link');
        links.forEach(link => {
            if(link.getAttribute('href') === currentPath) {
                link.classList.add('active');
            }
        });

        // Toast Notification Logic
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            },
            background: '#1e293b',
            color: '#fff'
        });

        // Check URL parameters for messages
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');
        const error = urlParams.get('error');

        if (msg) {
            let title = 'Success';
            let icon = 'success';
            
            switch(msg) {
                case 'created': title = 'Data created successfully'; break;
                case 'updated': title = 'Data updated successfully'; break;
                case 'deleted': title = 'Data deleted successfully'; break;
                case 'saved': title = 'Settings saved successfully'; break;
                default: title = msg;
            }

            Toast.fire({
                icon: icon,
                title: title
            });
            
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        if (error) {
            Toast.fire({
                icon: 'error',
                title: error
            });
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>
