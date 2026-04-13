<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    @vite('resources/css/app.css')
    @vite('resources/js/app.js')

</head>

<body>

    {{-- FOOTER --}}
    <footer class="bg-[#efeeea] dark:bg-[#121210]">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 px-12 py-16 w-full max-w-screen-2xl mx-auto">
            <div>
                <div class="text-lg font-black text-[#4d462e] dark:text-[#ede2c1] font-headline uppercase mb-6">
                    MAJELIS RENTAL
                </div>
                <p
                    class="font-label uppercase tracking-wider text-[10px] text-[#655e44] dark:text-[#ccc6b9] leading-relaxed">
                    PRECISION TOOLS FOR THE UNCOMPROMISING EXPLORER. BUILT FOR RELIABILITY, TESTED IN THE FIELD.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-3">
                    <p class="font-label uppercase tracking-wider text-[10px] font-bold text-[#4d462e]">Links</p>
                    <a class="font-label uppercase tracking-wider text-[10px] text-[#655e44] hover:text-[#4d462e] transition-opacity duration-200"
                        href="#">Basecamp Locations</a>
                    <a class="font-label uppercase tracking-wider text-[10px] text-[#655e44] hover:text-[#4d462e] transition-opacity duration-200"
                        href="#">Contact Support</a>
                </div>
                <div class="flex flex-col gap-3">
                    <p class="font-label uppercase tracking-wider text-[10px] font-bold text-[#4d462e]">Legal</p>
                    <a class="font-label uppercase tracking-wider text-[10px] text-[#655e44] hover:text-[#4d462e] transition-opacity duration-200"
                        href="#">Terms of Service</a>
                    <a class="font-label uppercase tracking-wider text-[10px] text-[#655e44] hover:text-[#4d462e] transition-opacity duration-200"
                        href="#">Privacy Policy</a>
                </div>
            </div>
            <div class="flex flex-col items-start md:items-end gap-6">

                <p
                    class="font-label uppercase tracking-wider text-[10px] text-[#655e44] dark:text-[#ccc6b9] text-left md:text-right">
                    © 2024 MAJELIS RENTAL - INDUSTRIAL PRECISION GEAR
                </p>
            </div>
        </div>
    </footer>
</body>

</html>
