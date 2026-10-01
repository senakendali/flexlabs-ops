@extends('layouts.ai-office')

@section('title', 'Academic AI Office')

@section('content')

<div
    id="academic-ai-office"
    class="relative h-full w-full overflow-hidden"
>

    {{-- ============================================================
        LUNA
    ============================================================ --}}

    <div
        class="
            absolute
            bottom-0
            left-[4%]
            z-20
            h-[86%]

            md:left-[5%]
            md:h-[88%]

            lg:left-[6%]
            lg:h-[90%]

            xl:left-[7%]
            xl:h-[92%]

            2xl:left-[9%]
        "
    >
        <img
            src="{{ asset('images/agent/01.png') }}"
            alt="Luna - Academic Secretary"
            class="
                h-full
                w-auto
                max-w-none
                select-none
                object-contain
                object-bottom
                drop-shadow-2xl
            "
            draggable="false"
        >
    </div>


    {{-- ============================================================
        LUNA GREETING BUBBLE
    ============================================================ --}}

    <div
        class="
            absolute
            left-[31%]
            top-[8%]
            z-30

            w-[310px]

            rounded-[1.5rem]

            border
            border-gray-200

            bg-white

            px-5
            py-4

            shadow-lg

            lg:left-[32%]

            xl:left-[31%]
            xl:top-[10%]
            xl:w-[350px]

            2xl:left-[32%]
            2xl:w-[370px]
        "
    >

        {{-- Bubble Tail --}}
        <div
            class="
                absolute
                -left-[10px]
                top-[45%]

                h-5
                w-5

                rotate-45

                border-b
                border-l
                border-gray-200

                bg-white
            "
        ></div>


        <div class="relative">

            <div class="flex items-center gap-2">

                <span
                    class="
                        h-2
                        w-2
                        rounded-full
                        bg-office-green
                    "
                ></span>

                <p
                    class="
                        text-[10px]
                        font-extrabold
                        uppercase
                        tracking-[0.16em]
                        text-office-primary
                    "
                >
                    Luna · Academic Secretary
                </p>

            </div>


            <h1
                class="
                    mt-2
                    text-xl
                    font-black
                    text-office-ink
                "
            >
                Halo, Mas! 👋
            </h1>


            <p
                class="
                    mt-1
                    text-sm
                    font-medium
                    leading-6
                    text-office-muted
                "
            >
                Selamat datang di Academic AI Office.
                Ada yang bisa saya bantu hari ini?
            </p>


            <div
                class="
                    mt-4
                    inline-flex
                    items-center
                    gap-2
                    rounded-full
                    bg-office-primarySoft
                    px-3
                    py-1.5
                "
            >

                <span
                    class="
                        h-2
                        w-2
                        rounded-full
                        bg-office-green
                    "
                ></span>

                <span
                    class="
                        text-[10px]
                        font-extrabold
                        text-office-primary
                    "
                >
                    Available
                </span>

            </div>

        </div>

    </div>


    {{-- ============================================================
        RIGHT WORKSPACE
    ============================================================ --}}

    <div
        class="
            absolute
            right-5
            top-5
            bottom-[110px]
            z-30

            hidden
            w-[360px]

            flex-col
            gap-3

            xl:flex

            2xl:right-7
            2xl:top-7
            2xl:w-[390px]
        "
    >

        {{-- ========================================================
            ACADEMIC TEAM
        ======================================================== --}}

        <section
            class="
                shrink-0

                rounded-[1.5rem]

                border
                border-gray-200

                bg-white

                p-4

                shadow-lg
            "
        >

            <div
                class="
                    flex
                    items-center
                    justify-between
                    gap-3
                "
            >

                <div>

                    <p
                        class="
                            text-[10px]
                            font-extrabold
                            uppercase
                            tracking-[0.16em]
                            text-office-primary
                        "
                    >
                        Academic Team
                    </p>

                    <p
                        class="
                            mt-1
                            text-xs
                            font-medium
                            text-office-muted
                        "
                    >
                        AI agents in this office
                    </p>

                </div>


                <div
                    class="
                        inline-flex
                        items-center
                        gap-1.5

                        rounded-full

                        bg-green-50

                        px-2.5
                        py-1
                    "
                >

                    <span
                        class="
                            h-1.5
                            w-1.5
                            rounded-full
                            bg-office-green
                        "
                    ></span>

                    <span
                        class="
                            text-[9px]
                            font-extrabold
                            text-office-green
                        "
                    >
                        4 Agents
                    </span>

                </div>

            </div>


            {{-- Agents --}}
            <div
                class="
                    mt-4
                    grid
                    grid-cols-4
                    gap-2
                "
            >

                {{-- Luna --}}
                <button
                    type="button"
                    class="
                        rounded-[1rem]

                        border
                        border-office-primary/20

                        bg-office-primarySoft

                        p-2

                        text-center

                        transition

                        hover:border-office-primary/40
                    "
                >

                    <div
                        class="
                            mx-auto

                            flex
                            h-11
                            w-11

                            items-center
                            justify-center

                            overflow-hidden

                            rounded-full

                            bg-white
                        "
                    >
                        <img
                            src="{{ asset('images/agent/01.png') }}"
                            alt="Luna"
                            class="
                                h-full
                                w-full
                                object-cover
                                object-top
                            "
                        >
                    </div>


                    <p
                        class="
                            mt-2
                            truncate
                            text-[10px]
                            font-extrabold
                            text-office-ink
                        "
                    >
                        Luna
                    </p>


                    <div
                        class="
                            mt-1
                            flex
                            items-center
                            justify-center
                            gap-1
                        "
                    >

                        <span
                            class="
                                h-1.5
                                w-1.5
                                rounded-full
                                bg-office-green
                            "
                        ></span>

                        <span
                            class="
                                text-[8px]
                                font-bold
                                text-office-muted
                            "
                        >
                            Active
                        </span>

                    </div>

                </button>


                {{-- Raka --}}
                <button
                    type="button"
                    class="
                        rounded-[1rem]

                        border
                        border-gray-200

                        bg-white

                        p-2

                        text-center

                        transition

                        hover:border-office-primary/30
                    "
                >

                    <div
                        class="
                            mx-auto

                            flex
                            h-11
                            w-11

                            items-center
                            justify-center

                            rounded-full

                            bg-office-primarySoft

                            text-xs

                            font-black

                            text-office-primary
                        "
                    >
                        RA
                    </div>

                    <p
                        class="
                            mt-2
                            truncate
                            text-[10px]
                            font-extrabold
                            text-office-ink
                        "
                    >
                        Raka
                    </p>

                    <div
                        class="
                            mt-1
                            flex
                            items-center
                            justify-center
                            gap-1
                        "
                    >

                        <span
                            class="
                                h-1.5
                                w-1.5
                                rounded-full
                                bg-office-green
                            "
                        ></span>

                        <span
                            class="
                                text-[8px]
                                font-bold
                                text-office-muted
                            "
                        >
                            Available
                        </span>

                    </div>

                </button>


                {{-- Bagas --}}
                <button
                    type="button"
                    class="
                        rounded-[1rem]

                        border
                        border-gray-200

                        bg-white

                        p-2

                        text-center

                        transition

                        hover:border-office-primary/30
                    "
                >

                    <div
                        class="
                            mx-auto

                            flex
                            h-11
                            w-11

                            items-center
                            justify-center

                            rounded-full

                            bg-office-primarySoft

                            text-xs

                            font-black

                            text-office-primary
                        "
                    >
                        BA
                    </div>

                    <p
                        class="
                            mt-2
                            truncate
                            text-[10px]
                            font-extrabold
                            text-office-ink
                        "
                    >
                        Bagas
                    </p>

                    <div
                        class="
                            mt-1
                            flex
                            items-center
                            justify-center
                            gap-1
                        "
                    >

                        <span
                            class="
                                h-1.5
                                w-1.5
                                rounded-full
                                bg-office-green
                            "
                        ></span>

                        <span
                            class="
                                text-[8px]
                                font-bold
                                text-office-muted
                            "
                        >
                            Available
                        </span>

                    </div>

                </button>


                {{-- Nova --}}
                <button
                    type="button"
                    class="
                        rounded-[1rem]

                        border
                        border-gray-200

                        bg-white

                        p-2

                        text-center

                        transition

                        hover:border-office-primary/30
                    "
                >

                    <div
                        class="
                            mx-auto

                            flex
                            h-11
                            w-11

                            items-center
                            justify-center

                            rounded-full

                            bg-office-primarySoft

                            text-xs

                            font-black

                            text-office-primary
                        "
                    >
                        NO
                    </div>

                    <p
                        class="
                            mt-2
                            truncate
                            text-[10px]
                            font-extrabold
                            text-office-ink
                        "
                    >
                        Nova
                    </p>

                    <div
                        class="
                            mt-1
                            flex
                            items-center
                            justify-center
                            gap-1
                        "
                    >

                        <span
                            class="
                                h-1.5
                                w-1.5
                                rounded-full
                                bg-office-green
                            "
                        ></span>

                        <span
                            class="
                                text-[8px]
                                font-bold
                                text-office-muted
                            "
                        >
                            Available
                        </span>

                    </div>

                </button>

            </div>

        </section>


        {{-- ========================================================
            CURRENT TASK
        ======================================================== --}}

        <section
            class="
                shrink-0

                rounded-[1.5rem]

                border
                border-gray-200

                bg-white

                p-4

                shadow-lg
            "
        >

            <div
                class="
                    flex
                    items-start
                    justify-between
                    gap-3
                "
            >

                <div>

                    <p
                        class="
                            text-[10px]
                            font-extrabold
                            uppercase
                            tracking-[0.16em]
                            text-office-primary
                        "
                    >
                        Current Task
                    </p>

                    <h2
                        class="
                            mt-1
                            text-sm
                            font-extrabold
                            text-office-ink
                        "
                    >
                        Academic Office is ready
                    </h2>

                </div>


                <div
                    class="
                        flex
                        h-9
                        w-9

                        shrink-0

                        items-center
                        justify-center

                        rounded-[1rem]

                        bg-office-primarySoft

                        text-office-primary
                    "
                >
                    <i
                        data-lucide="briefcase-business"
                        class="h-4 w-4"
                    ></i>
                </div>

            </div>


            <p
                class="
                    mt-2
                    text-xs
                    font-medium
                    leading-5
                    text-office-muted
                "
            >
                Send a request to Luna to start an academic workflow.
            </p>


            <div
                class="
                    mt-3
                    flex
                    items-center
                    gap-2
                "
            >

                <span
                    class="
                        h-2
                        w-2
                        rounded-full
                        bg-office-green
                    "
                ></span>

                <span
                    class="
                        text-[10px]
                        font-extrabold
                        text-office-muted
                    "
                >
                    Waiting for your instruction
                </span>

            </div>

        </section>


        {{-- ========================================================
            PROCESS TIMELINE
        ======================================================== --}}

        <section
            class="
                flex
                min-h-0
                flex-1
                flex-col

                rounded-[1.5rem]

                border
                border-gray-200

                bg-white

                p-4

                shadow-lg
            "
        >

            {{-- Header --}}
            <div
                class="
                    flex
                    shrink-0
                    items-center
                    justify-between
                    gap-3
                "
            >

                <div>

                    <p
                        class="
                            text-[10px]
                            font-extrabold
                            uppercase
                            tracking-[0.16em]
                            text-office-primary
                        "
                    >
                        Process
                    </p>

                    <p
                        class="
                            mt-1
                            text-xs
                            font-medium
                            text-office-muted
                        "
                    >
                        Agent workflow activity
                    </p>

                </div>


                <i
                    data-lucide="workflow"
                    class="
                        h-4
                        w-4
                        text-office-primary
                    "
                ></i>

            </div>


            {{-- Scroll Area --}}
            <div
                class="
                    ai-process-scroll

                    mt-4

                    min-h-0
                    flex-1

                    overflow-y-auto

                    pr-2
                "
            >

                {{-- Step 1 --}}
                <div class="relative flex gap-3 pb-4">

                    <div
                        class="
                            absolute
                            left-[7px]
                            top-4
                            bottom-0
                            w-px
                            bg-gray-200
                        "
                    ></div>

                    <div
                        class="
                            relative
                            z-10

                            mt-0.5

                            h-4
                            w-4

                            shrink-0

                            rounded-full

                            border-[4px]
                            border-office-primarySoft

                            bg-office-primary
                        "
                    ></div>


                    <div>

                        <p
                            class="
                                text-[11px]
                                font-extrabold
                                text-office-ink
                            "
                        >
                            Request
                        </p>

                        <p
                            class="
                                mt-0.5
                                text-[10px]
                                font-medium
                                text-office-muted
                            "
                        >
                            Waiting for user instruction
                        </p>

                    </div>

                </div>


                {{-- Step 2 --}}
                <div class="relative flex gap-3 pb-4">

                    <div
                        class="
                            absolute
                            left-[7px]
                            top-4
                            bottom-0
                            w-px
                            bg-gray-200
                        "
                    ></div>

                    <div
                        class="
                            relative
                            z-10

                            mt-0.5

                            h-4
                            w-4

                            shrink-0

                            rounded-full

                            border-[4px]
                            border-gray-100

                            bg-gray-300
                        "
                    ></div>


                    <div>

                        <p
                            class="
                                text-[11px]
                                font-extrabold
                                text-office-ink
                            "
                        >
                            Luna
                        </p>

                        <p
                            class="
                                mt-0.5
                                text-[10px]
                                font-medium
                                text-office-muted
                            "
                        >
                            Understand request & build workflow
                        </p>

                    </div>

                </div>


                {{-- Step 3 --}}
                <div class="relative flex gap-3 pb-4">

                    <div
                        class="
                            absolute
                            left-[7px]
                            top-4
                            bottom-0
                            w-px
                            bg-gray-200
                        "
                    ></div>

                    <div
                        class="
                            relative
                            z-10

                            mt-0.5

                            h-4
                            w-4

                            shrink-0

                            rounded-full

                            border-[4px]
                            border-gray-100

                            bg-gray-300
                        "
                    ></div>


                    <div>

                        <p
                            class="
                                text-[11px]
                                font-extrabold
                                text-office-ink
                            "
                        >
                            Academic Agent
                        </p>

                        <p
                            class="
                                mt-0.5
                                text-[10px]
                                font-medium
                                text-office-muted
                            "
                        >
                            Assigned agent starts working
                        </p>

                    </div>

                </div>


                {{-- Step 4 --}}
                <div class="relative flex gap-3 pb-4">

                    <div
                        class="
                            absolute
                            left-[7px]
                            top-4
                            bottom-0
                            w-px
                            bg-gray-200
                        "
                    ></div>

                    <div
                        class="
                            relative
                            z-10

                            mt-0.5

                            h-4
                            w-4

                            shrink-0

                            rounded-full

                            border-[4px]
                            border-gray-100

                            bg-gray-300
                        "
                    ></div>


                    <div>

                        <p
                            class="
                                text-[11px]
                                font-extrabold
                                text-office-ink
                            "
                        >
                            Review
                        </p>

                        <p
                            class="
                                mt-0.5
                                text-[10px]
                                font-medium
                                text-office-muted
                            "
                        >
                            Luna reviews the result
                        </p>

                    </div>

                </div>


                {{-- Step 5 --}}
                <div class="flex gap-3">

                    <div
                        class="
                            relative
                            z-10

                            mt-0.5

                            h-4
                            w-4

                            shrink-0

                            rounded-full

                            border-[4px]
                            border-gray-100

                            bg-gray-300
                        "
                    ></div>


                    <div>

                        <p
                            class="
                                text-[11px]
                                font-extrabold
                                text-office-ink
                            "
                        >
                            Completed
                        </p>

                        <p
                            class="
                                mt-0.5
                                text-[10px]
                                font-medium
                                text-office-muted
                            "
                        >
                            Result returned to user
                        </p>

                    </div>

                </div>

            </div>

        </section>

    </div>


    {{-- ============================================================
        CHAT BOX
    ============================================================ --}}

    <div
        class="
            absolute

            bottom-5
            left-1/2

            z-40

            w-[min(720px,calc(100%-40px))]

            -translate-x-1/2

            xl:left-[45%]
            xl:w-[640px]

            2xl:left-[46%]
            2xl:w-[700px]
        "
    >

        <form
            id="luna-chat-form"

            action="{{ route('academic.ai-office.message') }}"

            method="POST"

            class="
                flex
                items-center
                gap-3

                rounded-[1.5rem]

                border
                border-gray-200

                bg-white

                p-2

                shadow-xl
            "
        >

            @csrf


            {{-- Icon --}}
            <div
                class="
                    flex
                    h-11
                    w-11

                    shrink-0

                    items-center
                    justify-center

                    rounded-[1rem]

                    bg-office-primarySoft

                    text-office-primary
                "
            >

                <i
                    data-lucide="sparkles"
                    class="h-5 w-5"
                ></i>

            </div>


            {{-- Input --}}
            <input
                id="luna-message"

                type="text"

                name="message"

                placeholder="Tulis perintah untuk Luna..."

                autocomplete="off"

                class="
                    min-w-0
                    flex-1

                    border-0
                    bg-transparent

                    px-1
                    py-3

                    text-sm

                    font-medium

                    text-office-ink

                    outline-none

                    placeholder:text-office-muted/70

                    focus:border-0
                    focus:outline-none
                    focus:ring-0
                "
            >


            {{-- Send --}}
            <button
                type="submit"

                class="
                    inline-flex
                    h-11
                    w-11

                    shrink-0

                    items-center
                    justify-center

                    rounded-[1rem]

                    bg-office-primary

                    text-white

                    transition

                    hover:bg-office-primaryDark
                "

                aria-label="Kirim pesan"
            >

                <i
                    data-lucide="send"
                    class="h-4 w-4"
                ></i>

            </button>

        </form>


        <p
            class="
                mt-2

                text-center

                text-[10px]

                font-semibold

                text-white
            "
        >
            Contoh: “Cek kondisi kelas aktif hari ini.”
        </p>

    </div>


    {{-- ============================================================
        LOCAL STYLE
    ============================================================ --}}

    <style>

        /*
        |--------------------------------------------------------------------------
        | Process Scrollbar
        |--------------------------------------------------------------------------
        */

        .ai-process-scroll {
            scrollbar-width: thin;
            scrollbar-color: #CFC7DE transparent;
        }

        .ai-process-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .ai-process-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .ai-process-scroll::-webkit-scrollbar-thumb {
            background: #CFC7DE;
            border-radius: 999px;
        }

        .ai-process-scroll::-webkit-scrollbar-thumb:hover {
            background: #B6A8D0;
        }


        /*
        |--------------------------------------------------------------------------
        | Tablet
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1279px) {

            #academic-ai-office > div:nth-of-type(1) {
                left: 3%;
                height: 82%;
            }

            #academic-ai-office > div:nth-of-type(2) {
                left: 37%;
                top: 8%;
            }

        }


        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        @media (max-width: 767px) {

            #academic-ai-office > div:nth-of-type(1) {
                left: -45px;
                height: 66%;
            }


            #academic-ai-office > div:nth-of-type(2) {
                left: 16px;
                right: 16px;

                top: 16px;

                width: auto;
            }

        }

    </style>

</div>

@endsection