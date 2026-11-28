    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fceeee',
                            100: '#f9dede',
                            500: '#d83a3a',
                            600: '#c62828',
                            700: '#a82020',
                            800: '#8a1a1a',
                            900: '#6d1414',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    /* Tailwind's reset sizes every image; keep the school header and sidebar images at their original size. */
    .sidebar img, header img, .header img, .user_img { max-width: none !important; height: auto !important; }
    header img[height], [aria-expanded] img[height] { height: 42px !important; width: auto !important; }
    .sidebar .img_wrapper img { width: 40px; }
    .user_img, .header .user_img, .profile_img img, .header-user img, .navbar img.rounded-circle, .dropdown-toggle img { width: 40px !important; height: 40px !important; object-fit: cover; border-radius: 50%; }
</style>
