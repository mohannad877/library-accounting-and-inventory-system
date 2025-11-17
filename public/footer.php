<footer>
    <div class="footer-container">
        <div class="footer-section social">
            <h3>تابعنا</h3>
            <div class="social-icons">
                <a href="https://www.facebook.com/share/16BvsmqtiT/"><i class='bx bxl-facebook'></i></a>
                <a href="https://x.com/mo_31_m/"><i class='bx bxl-twitter'></i></a>
                <a href="https://www.instagram.com/mo_31_m/"><i class='bx bxl-instagram'></i></a>
                <a href="https://www.linkedin.com/in/mohannadnabil"><i class='bx bxl-linkedin'></i></a>
                <a href="https://www.youtube.com/@mo_31_m"><i class='bx bxl-youtube'></i></a>
                <a href="https://github.com/mohannad877/"><i class='bx bxl-github'></i></a>
            </div>
        </div>
    </div>
    <hr class="footer-sep">
    <div class="footer-bottom">
        <p>&copy; <span id="year"></span> By: Mohannad Nabeel Ahmed Mohammed Abdallah</p>
    </div>
    <style>
    footer {
        background: rgba(255, 255, 255, 0);
        color: rgb(0, 0, 0);
        padding: 12px 0 4px 0;
        text-align: center;
    }

    .footer-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: flex-start;
    }

    .footer-section.social {
        flex: 1;
        min-width: 120px;
        margin-bottom: 0;
        direction: rtl;
    }

    .footer-section.social h3 {
        font-size: 15px;
        margin-bottom: 4px;
        margin-top: 0;
        color: #222;
        font-weight: 600;
    }

    .social-icons {
        display: flex;
        flex-direction: row;
        gap: 15px;
        justify-content: center;
        align-items: center;
        margin: 0;
    }

    .social-icons a {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 35px;
        height: 35px;
        font-size: 15px;
        background: #ffffff00;
        color: black;
        border-radius: 50%;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.07);
        transition: transform 0.18s, color 0.18s;
        text-decoration: none;
    }

    body.dark-mode .social-icons a {
        color: #fff;
        box-shadow: 0 1px 2px rgba(255, 255, 255, 0.07);
    }

    .social-icons a:hover {
        color: #ffcc00;
        transform: scale(1.13);
    }

    .footer-sep {
        border: none;
        border-top: 1px solid #000;
        margin: 8px auto 4px auto;
        width: 45%;
    }

    .footer-bottom {
        margin-top: 0;
        padding-top: 2px;
        font-size: 9px;
        color: #888;
    }

    body.dark-mode footer {
        background: #111;
        color: white;
    }

    body.dark-mode .footer-bottom {
        color: #bbb;
        border-top: 1px solid #444;
    }

    body.dark-mode .footer-sep {
        border-top: 1px solid #fff;
    }

    @media (max-width: 600px) {
        .footer-section.social h3 {
            font-size: 13px;
        }

        .social-icons a {
            width: 22px;
            height: 22px;
            font-size: 12px;
        }

        .footer-bottom {
            font-size: 8px;
        }
    }
    </style>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script>
    document.getElementById('year').textContent = new Date().getFullYear();
    </script>
</footer>