@extends('layouts.ai-office')

@section('title', 'Academic AI Office')

@php
    /*
    |--------------------------------------------------------------------------
    | Logged In User
    |--------------------------------------------------------------------------
    |
    | Prioritas sapaan:
    | 1. users.salutation -> mas / mba / mbak
    | 2. users.gender     -> male / female / pria / wanita / l / p
    | 3. fallback         -> Kak
    |
    | Jangan menebak sapaan dari nama.
    |--------------------------------------------------------------------------
    */

    $loggedInUser = auth()->user();

    $loggedInUserName =
        $loggedInUser?->name
        ?? 'User';

    $loggedInUserFirstName = collect(
        preg_split('/\s+/', trim($loggedInUserName)) ?: []
    )->first() ?: 'User';

    $loggedInUserSalutationValue = strtolower(
        trim(
            (string) (
                $loggedInUser?->salutation
                ?? ''
            )
        )
    );

    $loggedInUserGenderValue = strtolower(
        trim(
            (string) (
                $loggedInUser?->gender
                ?? ''
            )
        )
    );

    $loggedInUserSalutation = match (true) {
        in_array(
            $loggedInUserSalutationValue,
            ['mas'],
            true
        ) => 'Mas',

        in_array(
            $loggedInUserSalutationValue,
            ['mba', 'mbak'],
            true
        ) => 'Mba',

        in_array(
            $loggedInUserGenderValue,
            ['male', 'pria', 'l', 'm'],
            true
        ) => 'Mas',

        in_array(
            $loggedInUserGenderValue,
            ['female', 'wanita', 'perempuan', 'p', 'f'],
            true
        ) => 'Mba',

        default => 'Kak',
    };

    $loggedInUserDisplayName = trim(
        $loggedInUserSalutation
        . ' '
        . $loggedInUserFirstName
    );
@endphp

@section('content')

<div
    id="academic-ai-office"
    class="relative h-full w-full overflow-hidden"
>

    {{-- ============================================================
        LUNA
    ============================================================ --}}

    <div
        id="luna-character"
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
            id="luna-character-image"
            src="{{ asset('images/agent/luna/luna_greeting.png') }}"
            alt="Luna - Academic Secretary"

            class="
                h-full
                w-auto
                max-w-none
                select-none
                object-contain
                object-bottom
                drop-shadow-2xl
                transition-opacity
                duration-150
            "

            draggable="false"
        >
    </div>


    {{-- ============================================================
        LUNA BUBBLE
    ============================================================ --}}

    <div
        id="luna-greeting"

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
                    id="luna-status-dot"
                    class="
                        h-2
                        w-2
                        rounded-full
                        bg-office-green
                    "
                ></span>


                <p
                    id="luna-role"
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


            {{-- Compatibility node only. Bubble tidak lagi memakai heading. --}}
            <p
                id="luna-greeting-title"
                class="hidden"
                aria-hidden="true"
            ></p>


            <p
                id="luna-greeting-message"
                class="
                    mt-2
                    text-sm
                    font-medium
                    leading-6
                    text-office-muted
                "
            >
                Halo, {{ $loggedInUserDisplayName }}! 👋 Selamat datang di Academic AI Office. Ada yang bisa saya bantu hari ini?
            </p>


            <div
                id="luna-loading-bar"
                class="
                    mt-3
                    hidden
                    h-1.5
                    overflow-hidden
                    rounded-full
                    bg-office-primarySoft
                "
                aria-hidden="true"
            >
                <div
                    class="
                        luna-loading-bar-indicator
                        h-full
                        w-1/3
                        rounded-full
                        bg-office-primary
                    "
                ></div>
            </div>


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
                    id="luna-status-badge-dot"
                    class="
                        h-2
                        w-2
                        rounded-full
                        bg-office-green
                    "
                ></span>


                <span
                    id="luna-status-text"
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
        id="academic-workspace"
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
                        id="academic-agent-count"
                        class="
                            text-[9px]
                            font-extrabold
                            text-office-green
                        "
                    >
                        0 Agents
                    </span>

                </div>

            </div>


            <div
                id="academic-agent-list"
                class="
                    mt-4
                    grid
                    grid-cols-4
                    gap-2
                "
            ></div>

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

                <div class="min-w-0">

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
                        id="current-task-title"
                        class="
                            mt-1
                            line-clamp-2
                            text-sm
                            font-extrabold
                            leading-5
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
                id="current-task-description"
                class="
                    mt-2
                    line-clamp-2
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
                    id="current-task-dot"
                    class="
                        h-2
                        w-2
                        rounded-full
                        bg-office-green
                    "
                ></span>


                <span
                    id="current-task-status"
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
            PROCESS
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


            <div
                id="process-timeline"
                class="
                    ai-process-scroll
                    mt-4
                    min-h-0
                    flex-1
                    overflow-y-auto
                    pr-2
                "
            >

                <div class="flex gap-3">

                    <div
                        class="
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
                            Waiting for Request
                        </p>


                        <p
                            class="
                                mt-0.5
                                text-[10px]
                                font-medium
                                text-office-muted
                            "
                        >
                            Send an instruction to Luna to start.
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


            <button
                id="luna-send-button"
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


        @keyframes luna-loading-slide {

            0% {
                transform: translateX(-120%);
            }

            100% {
                transform: translateX(320%);
            }

        }


        .luna-loading-bar-indicator {
            animation:
                luna-loading-slide
                1.15s
                ease-in-out
                infinite;
        }


        @media (max-width: 1279px) {

            #luna-character {
                left: 3%;
                height: 82%;
            }

            #luna-greeting {
                left: 37%;
                top: 8%;
            }

        }


        @media (max-width: 767px) {

            #luna-character {
                left: -45px;
                height: 66%;
            }

            #luna-greeting {
                left: 16px;
                right: 16px;
                top: 16px;
                width: auto;
            }

        }

    </style>

</div>

@endsection


@push('scripts')

<script>
    document.addEventListener('DOMContentLoaded', function () {

        /*
        |--------------------------------------------------------------------------
        | URLs
        |--------------------------------------------------------------------------
        */

        const stateUrl =
            @json(route('academic.ai-office.state'));

        const messageUrl =
            @json(route('academic.ai-office.message'));


        /*
        |--------------------------------------------------------------------------
        | Current User
        |--------------------------------------------------------------------------
        */

        const currentUser = {
            name:
                @json($loggedInUserName),

            firstName:
                @json($loggedInUserFirstName),

            salutation:
                @json($loggedInUserSalutation),

            displayName:
                @json($loggedInUserDisplayName),
        };


        /*
        |--------------------------------------------------------------------------
        | Elements
        |--------------------------------------------------------------------------
        */

        const lunaImage =
            document.getElementById('luna-character-image');

        const lunaRole =
            document.getElementById('luna-role');

        const lunaStatusText =
            document.getElementById('luna-status-text');

        const lunaGreetingTitle =
            document.getElementById('luna-greeting-title');

        const lunaGreetingMessage =
            document.getElementById('luna-greeting-message');

        const lunaLoadingBar =
            document.getElementById('luna-loading-bar');


        const agentList =
            document.getElementById('academic-agent-list');

        const agentCount =
            document.getElementById('academic-agent-count');


        const currentTaskTitle =
            document.getElementById('current-task-title');

        const currentTaskDescription =
            document.getElementById('current-task-description');

        const currentTaskStatus =
            document.getElementById('current-task-status');

        const currentTaskDot =
            document.getElementById('current-task-dot');


        const processTimeline =
            document.getElementById('process-timeline');


        const chatForm =
            document.getElementById('luna-chat-form');

        const messageInput =
            document.getElementById('luna-message');

        const submitButton =
            document.getElementById('luna-send-button');


        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');


        /*
        |--------------------------------------------------------------------------
        | Runtime Data
        |--------------------------------------------------------------------------
        */

        let officeState = null;

        let primaryAgent = null;

        let activeAgent = null;

        let activeAgentAvatarStates = {};

        let mainAgentImageRequestId = 0;

        let officeReady = false;


        /*
        |--------------------------------------------------------------------------
        | Handoff Presentation Timing
        |--------------------------------------------------------------------------
        |
        | Workflow backend masih synchronous pada MVP ini.
        | Timing di bawah hanya memberi ruang agar perpindahan
        | Luna → specialist → Luna bisa terbaca oleh user.
        |
        | Saat realtime workflow/event sudah dipakai, bagian ini
        | bisa diganti dengan state progress yang benar-benar live.
        |--------------------------------------------------------------------------
        */

        const handoffPresentationTiming = {
            delegation: 1800,
            specialistWorking: 900,
            specialistCompleted: 750,
            returnToLuna: 900,
        };


        /*
        |--------------------------------------------------------------------------
        | Conversational Presentation Timing
        |--------------------------------------------------------------------------
        |
        | Direct conversation tetap punya visual state:
        |
        | listening → thinking → explaining
        |
        | Nilai kecil ini hanya memastikan perubahan avatar sempat terlihat.
        | Request backend tetap dimulai secepat mungkin dan berjalan paralel.
        |--------------------------------------------------------------------------
        */

        const conversationPresentationTiming = {
            listeningMinimum: 320,
            thinkingMinimum: 420,
        };


        function waitForPresentation(
            milliseconds
        ) {

            return new Promise(
                resolve =>
                    window.setTimeout(
                        resolve,
                        milliseconds
                    )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Image Preload Cache
        |--------------------------------------------------------------------------
        */

        const imagePreloadCache =
            new Map();


        function preloadImage(url) {

            if (!url) {
                return Promise.resolve(false);
            }


            if (
                imagePreloadCache.has(url)
            ) {
                return imagePreloadCache.get(url);
            }


            const promise =
                new Promise(resolve => {

                    const image =
                        new Image();


                    image.onload =
                        function () {
                            resolve(true);
                        };


                    image.onerror =
                        function () {

                            console.error(
                                'AI Office avatar preload failed:',
                                url
                            );

                            resolve(false);
                        };


                    image.src =
                        url;
                });


            imagePreloadCache.set(
                url,
                promise
            );


            return promise;
        }


        async function preloadAgentAvatars(
            avatars
        ) {

            if (
                !avatars
                ||
                typeof avatars !== 'object'
            ) {
                return;
            }


            const urls = [
                ...new Set(
                    Object
                        .values(avatars)
                        .filter(Boolean)
                ),
            ];


            await Promise.all(
                urls.map(
                    url =>
                        preloadImage(url)
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Safe Luna Image Swap
        |--------------------------------------------------------------------------
        */

        async function setMainAgentImage(
            url
        ) {

            if (
                !url
                ||
                !lunaImage
            ) {
                return false;
            }


            const requestId =
                ++mainAgentImageRequestId;


            const loaded =
                await preloadImage(url);


            /*
            |--------------------------------------------------------------------------
            | Ignore Old State Request
            |--------------------------------------------------------------------------
            */

            if (
                requestId
                !== mainAgentImageRequestId
            ) {
                return false;
            }


            if (!loaded) {

                const fallback =
                    activeAgentAvatarStates.idle
                    ?? activeAgentAvatarStates.greeting
                    ?? null;


                if (
                    fallback
                    &&
                    fallback !== url
                ) {

                    const fallbackLoaded =
                        await preloadImage(
                            fallback
                        );


                    if (
                        fallbackLoaded
                        &&
                        requestId
                            === mainAgentImageRequestId
                    ) {

                        lunaImage.src =
                            fallback;
                    }
                }


                return false;
            }


            /*
            |--------------------------------------------------------------------------
            | Swap Image
            |--------------------------------------------------------------------------
            */

            lunaImage.style.opacity =
                '0.88';


            lunaImage.src =
                url;


            await new Promise(
                resolve =>
                    requestAnimationFrame(
                        resolve
                    )
            );


            if (
                requestId
                === mainAgentImageRequestId
            ) {

                lunaImage.style.opacity =
                    '1';
            }


            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | Main Agent Image Error Protection
        |--------------------------------------------------------------------------
        */

        lunaImage?.addEventListener(
            'error',

            async function () {

                const failedUrl =
                    lunaImage.currentSrc
                    || lunaImage.src;


                console.error(
                    'Main agent avatar failed to render:',
                    failedUrl
                );


                const fallback =
                    activeAgentAvatarStates.idle
                    ?? activeAgentAvatarStates.greeting
                    ?? null;


                if (
                    !fallback
                    ||
                    failedUrl === fallback
                ) {
                    return;
                }


                const loaded =
                    await preloadImage(
                        fallback
                    );


                if (loaded) {
                    lunaImage.src =
                        fallback;
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Conversation Storage
        |--------------------------------------------------------------------------
        */

        const conversationStorageKey =
            'flexops_academic_ai_conversation_id';


        function getConversationId() {

            const value =
                sessionStorage.getItem(
                    conversationStorageKey
                );


            if (!value) {
                return null;
            }


            const id =
                Number(value);


            return Number.isInteger(id)
                && id > 0
                    ? id
                    : null;
        }


        function setConversationId(
            id
        ) {

            if (!id) {
                return;
            }


            sessionStorage.setItem(
                conversationStorageKey,
                String(id)
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Bubble Loading Bar
        |--------------------------------------------------------------------------
        */

        function setBubbleLoading(
            isLoading
        ) {

            if (!lunaLoadingBar) {
                return;
            }


            lunaLoadingBar.classList.toggle(
                'hidden',
                !isLoading
            );


            lunaLoadingBar.setAttribute(
                'aria-hidden',
                isLoading
                    ? 'false'
                    : 'true'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | State Labels
        |--------------------------------------------------------------------------
        */

        const stateLabels = {
            idle: 'Available',
            greeting: 'Available',
            listening: 'Listening',
            thinking: 'Thinking',
            working: 'Working',
            waiting: 'Waiting',
            waiting_approval: 'Waiting Approval',
            completed: 'Completed',
            questioning: 'Need Clarification',
            explaining: 'Explaining',
            error: 'Needs Attention',
        };


        function formatState(
            state
        ) {

            return stateLabels[state]
                ?? state
                ?? 'Unknown';
        }


        /*
        |--------------------------------------------------------------------------
        | Conversational State Copy
        |--------------------------------------------------------------------------
        |
        | Avatar state tetap:
        |
        | listening → thinking → explaining
        |
        | Tetapi copy sementara menyesuaikan isi pesan user supaya Luna tidak
        | terdengar seperti sedang "menganalisis pekerjaan" ketika user hanya
        | menyapa, berterima kasih, atau memberi acknowledgement.
        |
        | Ini hanya presentation heuristic di frontend.
        | Intent dan routing sebenarnya tetap ditentukan Luna AI Planner.
        |--------------------------------------------------------------------------
        */

        function getConversationStateCopy(
            message
        ) {

            const normalized =
                String(
                    message
                    ?? ''
                )
                    .trim()
                    .toLowerCase();


            /*
            |--------------------------------------------------------------------------
            | Thanks
            |--------------------------------------------------------------------------
            */

            if (
                /\b(terima\s*kasih|makasih|makasi|thanks|thank\s*you|thx)\b/i
                    .test(normalized)
            ) {

                return {
                    listening: {
                        title: '',
                        message: 'Sama-sama. 😊',
                    },

                    thinking: {
                        title: '',
                        message: 'Siap.',
                    },
                };
            }


            /*
            |--------------------------------------------------------------------------
            | Greeting
            |--------------------------------------------------------------------------
            */

            if (
                /^(halo|hai|hi|hello|pagi|selamat\s+pagi|siang|selamat\s+siang|sore|selamat\s+sore|malam|selamat\s+malam)\b/i
                    .test(normalized)
            ) {

                return {
                    listening: {
                        title: '',
                        message: `Halo, ${currentUser.displayName}. 👋`,
                    },

                    thinking: {
                        title: '',
                        message: 'Saya di sini.',
                    },
                };
            }


            /*
            |--------------------------------------------------------------------------
            | Simple Acknowledgement
            |--------------------------------------------------------------------------
            */

            if (
                /^(oke|ok|okay|sip|siap|baik|yoi|mantap|noted|gas|lanjut)\b/i
                    .test(normalized)
            ) {

                return {
                    listening: {
                        title: '',
                        message: 'Siap.',
                    },

                    thinking: {
                        title: '',
                        message: 'Oke.',
                    },
                };
            }


            /*
            |--------------------------------------------------------------------------
            | Contextual Follow-up
            |--------------------------------------------------------------------------
            */

            if (
                /\b(yang\s+tadi|yang\s+itu|yang\s+ini|kalau|kalo|terus|lanjutannya|gimana|bagaimana|dia|itu\s+gimana|yang\s+lain|yang\s+lainnya)\b/i
                    .test(normalized)
            ) {

                return {
                    listening: {
                        title: '',
                        message: 'Oke, saya ikuti.',
                    },

                    thinking: {
                        title: '',
                        message: 'Saya cek dulu yang tadi.',
                    },
                };
            }


            /*
            |--------------------------------------------------------------------------
            | Likely Operational Request
            |--------------------------------------------------------------------------
            */

            if (
                /^(cek|tolong\s+cek|buat|buatkan|bikin|ubah|update|cari|lihat|tampilkan|ambil|jadwalkan|susun|siapkan|buatkanlah)\b/i
                    .test(normalized)
            ) {

                return {
                    listening: {
                        title: '',
                        message: 'Baik, saya cek dulu.',
                    },

                    thinking: {
                        title: '',
                        message: 'Sebentar ya.',
                    },
                };
            }


            /*
            |--------------------------------------------------------------------------
            | Generic Conversation
            |--------------------------------------------------------------------------
            */

            return {
                listening: {
                    title: '',
                    message: 'Oke, saya dengarkan.',
                },

                thinking: {
                    title: '',
                    message: 'Sebentar ya.',
                },
            };
        }


        /*
        |--------------------------------------------------------------------------
        | Workflow Status Labels
        |--------------------------------------------------------------------------
        */

        const workflowStatusLabels = {
            pending: 'Pending',
            running: 'In Progress',
            waiting: 'Waiting',
            waiting_approval: 'Waiting Approval',
            completed: 'Completed',
            failed: 'Failed',
            cancelled: 'Cancelled',
        };


        function formatWorkflowStatus(
            status
        ) {

            return workflowStatusLabels[status]
                ?? status
                ?? 'Unknown';
        }


        /*
        |--------------------------------------------------------------------------
        | Luna Bubble Content
        |--------------------------------------------------------------------------
        |
        | Greeting hanya digunakan saat pertama kali office dibuka.
        |
        | Setelah user mulai berinteraksi, bubble mengikuti state
        | pekerjaan terakhir.
        |--------------------------------------------------------------------------
        */

        const agentStateContent = {

            greeting: {
                title:
                    `Halo, ${currentUser.displayName}! 👋`,

                message:
                    'Selamat datang di Academic AI Office. Ada yang bisa saya bantu hari ini?',
            },


            listening: {
                title: '',

                message:
                    'Oke, saya dengarkan.',
            },


            thinking: {
                title: '',

                message:
                    'Sebentar ya.',
            },


            working: {
                title: '',

                message:
                    'Sedang saya kerjakan.',
            },


            waiting: {
                title: '',

                message:
                    'Masih saya tunggu sebentar.',
            },


            waiting_approval: {
                title:
                    `Menunggu persetujuan ${currentUser.displayName}.`,

                message:
                    'Ada tindakan yang perlu disetujui sebelum proses dilanjutkan.',
            },


            completed: {
                title: '',

                message:
                    'Sudah selesai. ✓',
            },


            questioning: {
                title: '',

                message:
                    'Ada sedikit yang perlu saya pastikan dulu.',
            },


            explaining: {
                title: '',

                message:
                    'Ini hasilnya.',
            },


            error: {
                title: '',

                message:
                    'Ada kendala. Coba lagi sebentar ya.',
            },

        };


        /*
        |--------------------------------------------------------------------------
        | Set Main Agent State
        |--------------------------------------------------------------------------
        |
        | Karakter utama tidak lagi hardcoded ke Luna.
        | Agent yang tampil mengikuti agent yang sedang aktif.
        |--------------------------------------------------------------------------
        */

        async function setMainAgentState(
            agent,
            state,
            options = {}
        ) {

            if (!agent) {
                return;
            }


            activeAgent =
                agent;


            activeAgentAvatarStates =
                agent.avatars
                ?? {};


            await preloadAgentAvatars(
                activeAgentAvatarStates
            );


            /*
            |--------------------------------------------------------------------------
            | Agent Header
            |--------------------------------------------------------------------------
            */

            if (lunaRole) {

                lunaRole.textContent =
                    `${agent.name} · ${agent.role}`;
            }


            if (lunaImage) {

                lunaImage.alt =
                    `${agent.name} - ${agent.role}`;
            }


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            if (lunaStatusText) {

                lunaStatusText.textContent =
                    formatState(state);
            }


            /*
            |--------------------------------------------------------------------------
            | Bubble Content
            |--------------------------------------------------------------------------
            |
            | Untuk state thinking:
            |
            | - teks acknowledgement sebelumnya dipertahankan
            | - progress bar ditampilkan
            | - tidak ada lagi copy seperti "Saya di sini" / "Sebentar ya"
            |
            | State lain tetap memakai satu conversational text block.
            |--------------------------------------------------------------------------
            */

            const isThinking =
                state === 'thinking';


            setBubbleLoading(
                isThinking
            );


            const defaultContent =
                agentStateContent[state]
                ?? null;


            const title =
                options.title
                ?? defaultContent?.title
                ?? '';


            const message =
                options.message
                ?? defaultContent?.message
                ?? '';


            if (lunaGreetingTitle) {

                lunaGreetingTitle.textContent =
                    '';

                lunaGreetingTitle.classList.add(
                    'hidden'
                );
            }


            if (
                !isThinking
                &&
                !options.preserveContent
                &&
                lunaGreetingMessage
            ) {

                const bubbleParts =
                    [
                        title,
                        message,
                    ]
                        .map(
                            item =>
                                String(
                                    item
                                    ?? ''
                                ).trim()
                        )
                        .filter(Boolean);


                lunaGreetingMessage.textContent =
                    bubbleParts.join(
                        ' '
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | Avatar
            |--------------------------------------------------------------------------
            */

            const avatarUrl =
                activeAgentAvatarStates[state]
                ?? agent.avatar_url
                ?? activeAgentAvatarStates.idle
                ?? activeAgentAvatarStates.greeting
                ?? null;


            if (!avatarUrl) {
                return;
            }


            await setMainAgentImage(
                avatarUrl
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Set Luna State
        |--------------------------------------------------------------------------
        |
        | Luna tetap menjadi primary/orchestrator.
        | Wrapper ini dipakai saat user baru mengirim instruksi.
        |--------------------------------------------------------------------------
        */

        async function setLunaState(
            state,
            options = {}
        ) {

            const luna =
                primaryAgent
                ?? officeState?.primary_agent
                ?? null;


            if (!luna) {
                return;
            }


            await setMainAgentState(
                luna,
                state,
                options
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Agent Initial
        |--------------------------------------------------------------------------
        */

        function getInitials(
            name
        ) {

            if (!name) {
                return 'AI';
            }


            return name
                .trim()
                .split(/\s+/)
                .slice(0, 2)
                .map(
                    part =>
                        part
                            .charAt(0)
                            .toUpperCase()
                )
                .join('');
        }


        /*
        |--------------------------------------------------------------------------
        | Agent Card
        |--------------------------------------------------------------------------
        |
        | Academic Team sekarang sengaja dibuat name-only.
        | Tidak memakai thumbnail/avatar supaya panel lebih clean.
        |--------------------------------------------------------------------------
        */

        function createAgentCard(
            agent
        ) {

            const button =
                document.createElement(
                    'button'
                );


            button.type =
                'button';


            button.className = [
                'min-w-0',
                'rounded-[0.9rem]',
                'border',

                agent.code === 'luna'
                    ? 'border-office-primary/20'
                    : 'border-gray-200',

                agent.code === 'luna'
                    ? 'bg-office-primarySoft'
                    : 'bg-white',

                'px-2.5',
                'py-2.5',
                'text-center',
                'transition',
                'hover:border-office-primary/30',
                'hover:bg-office-primarySoft/40',
            ].join(' ');


            const name =
                document.createElement(
                    'p'
                );


            name.className = [
                'truncate',
                'text-[10px]',
                'font-extrabold',

                agent.code === 'luna'
                    ? 'text-office-primary'
                    : 'text-office-ink',
            ].join(' ');


            name.textContent =
                agent.name;


            button.appendChild(
                name
            );


            return button;
        }


        /*
        |--------------------------------------------------------------------------
        | Render Office State
        |--------------------------------------------------------------------------
        */

        async function renderOfficeState(
            data
        ) {

            officeState =
                data;


            primaryAgent =
                data.primary_agent;


            if (primaryAgent) {

                /*
                |--------------------------------------------------------------------------
                | Initial Main Agent
                |--------------------------------------------------------------------------
                |
                | Saat office pertama dibuka, Luna tetap menjadi karakter utama.
                |--------------------------------------------------------------------------
                */

                await setMainAgentState(
                    primaryAgent,
                    'greeting'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Academic Team
            |--------------------------------------------------------------------------
            */

            const agents =
                Array.isArray(
                    data.agents
                )
                    ? data.agents
                    : [];


            if (agentCount) {

                agentCount.textContent =
                    `${agents.length} ${
                        agents.length === 1
                            ? 'Agent'
                            : 'Agents'
                    }`;
            }


            if (agentList) {

                agentList.innerHTML =
                    '';


                agents.forEach(
                    agent => {

                        agentList.appendChild(
                            createAgentCard(
                                agent
                            )
                        );
                    }
                );
            }


            refreshIcons();
        }


        /*
        |--------------------------------------------------------------------------
        | Process Step Dot
        |--------------------------------------------------------------------------
        */

        function getStepDotClasses(
            status
        ) {

            switch (status) {

                case 'completed':

                    return [
                        'border-green-100',
                        'bg-office-green',
                    ];


                case 'running':

                    return [
                        'border-office-primarySoft',
                        'bg-office-primary',
                    ];


                case 'waiting':

                case 'waiting_approval':

                    return [
                        'border-yellow-100',
                        'bg-office-yellow',
                    ];


                case 'failed':

                    return [
                        'border-red-100',
                        'bg-red-500',
                    ];


                default:

                    return [
                        'border-gray-100',
                        'bg-gray-300',
                    ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Render Workflow
        |--------------------------------------------------------------------------
        */

        function renderWorkflow(
            workflow
        ) {

            if (!workflow) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Current Task
            |--------------------------------------------------------------------------
            |
            | Percakapan langsung dengan Luna bukan operational task.
            |
            | Jadi untuk respond_directly:
            | - jangan tampilkan isi chat sebagai Current Task
            | - reset panel ke kondisi office ready
            |
            | Operational request seperti specialist execution atau
            | unsupported execution tetap ditampilkan sebagai Current Task.
            |--------------------------------------------------------------------------
            */

            const isDirectConversation =
                workflow.resolved_action
                === 'respond_directly';


            if (isDirectConversation) {

                if (currentTaskTitle) {

                    currentTaskTitle.textContent =
                        'Academic Office is ready';
                }


                if (currentTaskDescription) {

                    currentTaskDescription.textContent =
                        'Send an academic request to Luna when you need operational support.';
                }


                if (currentTaskStatus) {

                    currentTaskStatus.textContent =
                        'Waiting for your instruction';
                }

            } else {

                if (currentTaskTitle) {

                    currentTaskTitle.textContent =
                        workflow.title
                        ?? 'Academic Request';
                }


                if (currentTaskDescription) {

                    currentTaskDescription.textContent =
                        workflow.objective
                        ?? workflow.title
                        ?? '';
                }


                if (currentTaskStatus) {

                    currentTaskStatus.textContent =
                        formatWorkflowStatus(
                            workflow.status
                        );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Timeline
            |--------------------------------------------------------------------------
            */

            if (!processTimeline) {
                return;
            }


            const steps =
                Array.isArray(
                    workflow.steps
                )
                    ? workflow.steps
                    : [];


            processTimeline.innerHTML =
                '';


            if (
                steps.length === 0
            ) {

                processTimeline.innerHTML = `
                    <div class="flex gap-3">

                        <div
                            class="
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
                                Workflow created
                            </p>

                            <p
                                class="
                                    mt-0.5
                                    text-[10px]
                                    font-medium
                                    text-office-muted
                                "
                            >
                                Waiting for process steps.
                            </p>

                        </div>

                    </div>
                `;

                return;
            }


            steps.forEach(
                (step, index) => {

                    const item =
                        document.createElement(
                            'div'
                        );


                    item.className =
                        'relative flex gap-3 pb-4';


                    /*
                    |--------------------------------------------------------------------------
                    | Connector
                    |--------------------------------------------------------------------------
                    */

                    if (
                        index
                        <
                        steps.length - 1
                    ) {

                        const connector =
                            document.createElement(
                                'div'
                            );


                        connector.className = [
                            'absolute',
                            'left-[7px]',
                            'top-4',
                            'bottom-0',
                            'w-px',
                            'bg-gray-200',
                        ].join(' ');


                        item.appendChild(
                            connector
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Dot
                    |--------------------------------------------------------------------------
                    */

                    const dot =
                        document.createElement(
                            'div'
                        );


                    dot.className = [
                        'relative',
                        'z-10',
                        'mt-0.5',
                        'h-4',
                        'w-4',
                        'shrink-0',
                        'rounded-full',
                        'border-[4px]',
                        ...getStepDotClasses(
                            step.status
                        ),
                    ].join(' ');


                    /*
                    |--------------------------------------------------------------------------
                    | Content
                    |--------------------------------------------------------------------------
                    */

                    const content =
                        document.createElement(
                            'div'
                        );


                    content.className =
                        'min-w-0 flex-1';


                    const header =
                        document.createElement(
                            'div'
                        );


                    header.className = [
                        'flex',
                        'items-start',
                        'justify-between',
                        'gap-2',
                    ].join(' ');


                    const title =
                        document.createElement(
                            'p'
                        );


                    title.className = [
                        'text-[11px]',
                        'font-extrabold',
                        'text-office-ink',
                    ].join(' ');


                    title.textContent =
                        step.name
                        ?? 'Workflow Step';


                    const status =
                        document.createElement(
                            'span'
                        );


                    status.className = [
                        'shrink-0',
                        'text-[8px]',
                        'font-extrabold',
                        'uppercase',
                        'tracking-[0.08em]',
                        'text-office-muted',
                    ].join(' ');


                    status.textContent =
                        formatWorkflowStatus(
                            step.status
                        );


                    header.appendChild(
                        title
                    );

                    header.appendChild(
                        status
                    );


                    const description =
                        document.createElement(
                            'p'
                        );


                    description.className = [
                        'mt-0.5',
                        'text-[10px]',
                        'font-medium',
                        'leading-4',
                        'text-office-muted',
                    ].join(' ');


                    description.textContent =
                        step.description
                        ?? '';


                    content.appendChild(
                        header
                    );

                    content.appendChild(
                        description
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Agent
                    |--------------------------------------------------------------------------
                    */

                    if (
                        step.agent?.name
                    ) {

                        const agent =
                            document.createElement(
                                'p'
                            );


                        agent.className = [
                            'mt-1',
                            'text-[9px]',
                            'font-extrabold',
                            'text-office-primary',
                        ].join(' ');


                        agent.textContent =
                            `${step.agent.name} · ${step.agent.role ?? ''}`;


                        content.appendChild(
                            agent
                        );
                    }


                    item.appendChild(
                        dot
                    );

                    item.appendChild(
                        content
                    );


                    processTimeline.appendChild(
                        item
                    );
                }
            );


            processTimeline.scrollTop =
                0;


            refreshIcons();
        }


        /*
        |--------------------------------------------------------------------------
        | Assistant Message Presentation
        |--------------------------------------------------------------------------
        |
        | Response AI ditampilkan sebagai satu conversational text block.
        | Tidak lagi memecah kalimat pertama menjadi heading besar karena
        | hasilnya terasa kaku untuk percakapan natural.
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Render Luna Assistant Message
        |--------------------------------------------------------------------------
        |
        | Dipakai untuk response user-facing yang sudah dihasilkan backend:
        |
        | - respond_directly
        | - unsupported
        |
        | Untuk specialist flow yang belum punya assistant_message,
        | frontend tetap memakai workflow handoff yang sudah ada.
        |--------------------------------------------------------------------------
        */

        async function renderAssistantMessage(
            assistantMessage
        ) {

            const content =
                String(
                    assistantMessage?.content
                    ?? ''
                )
                    .trim();


            if (!content) {
                return false;
            }


            /*
            |--------------------------------------------------------------------------
            | Resolve Agent
            |--------------------------------------------------------------------------
            |
            | assistant_message.agent dari API belum membawa daftar avatar.
            | Karena saat ini response user-facing datang dari Luna,
            | gabungkan payload agent dengan primaryAgent supaya avatar states
            | tetap tersedia.
            |--------------------------------------------------------------------------
            */

            const assistantAgent = {
                ...(primaryAgent ?? {}),
                ...(assistantMessage.agent ?? {}),

                avatars:
                    primaryAgent?.avatars
                    ?? {},

                avatar_url:
                    primaryAgent?.avatar_url
                    ?? null,
            };


            /*
            |--------------------------------------------------------------------------
            | Show Natural Response
            |--------------------------------------------------------------------------
            |
            | Final conversational answer memakai satu body text block.
            | Tidak ada sentence yang dipaksa menjadi heading.
            |
            | Direct conversation berakhir di explaining, bukan completed:
            |
            | listening → thinking → explaining
            |--------------------------------------------------------------------------
            */

            if (lunaGreetingTitle) {

                lunaGreetingTitle.textContent =
                    '';

                lunaGreetingTitle.classList.add(
                    'hidden'
                );
            }


            setBubbleLoading(
                false
            );


            await setMainAgentState(
                assistantAgent,
                'explaining',
                {
                    preserveContent:
                        true,
                }
            );


            if (lunaGreetingMessage) {

                lunaGreetingMessage.textContent =
                    content;
            }


            return true;
        }


        /*
        |--------------------------------------------------------------------------
        | Apply Workflow Final Agent
        |--------------------------------------------------------------------------
        |
        | Dipakai untuk workflow yang tidak membutuhkan specialist,
        | atau sebagai fallback ketika payload handoff tidak tersedia.
        |--------------------------------------------------------------------------
        */

        async function applyWorkflowFinalAgent(
            workflow
        ) {

            if (!workflow) {
                return;
            }


            const workflowAgent =
                workflow.active_agent
                ?? primaryAgent
                ?? null;


            const workflowState =
                workflow.active_state
                ?? (
                    workflow.status === 'completed'
                        ? 'completed'
                        : workflow.status === 'failed'
                            ? 'error'
                            : workflow.status === 'waiting_approval'
                                ? 'waiting_approval'
                                : workflow.status === 'waiting'
                                    ? 'waiting'
                                    : workflow.status === 'running'
                                        ? 'working'
                                        : 'thinking'
                );


            if (!workflowAgent) {
                return;
            }


            const options = {};


            if (
                workflowState === 'completed'
            ) {

                options.title =
                    `Selesai, ${currentUser.displayName}. ✓`;


                options.message =
                    `Proses "${workflow.title ?? 'Academic Request'}" sudah selesai.`;
            }


            if (
                workflowState === 'waiting_approval'
            ) {

                options.message =
                    `Proses "${workflow.title ?? 'Academic Request'}" membutuhkan persetujuan sebelum dilanjutkan.`;
            }


            if (
                workflowState === 'error'
            ) {

                options.message =
                    `Proses "${workflow.title ?? 'Academic Request'}" mengalami kendala.`;
            }


            await setMainAgentState(
                workflowAgent,
                workflowState,
                options
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Build Luna Final Response
        |--------------------------------------------------------------------------
        |
        | Untuk sekarang response masih deterministic dari structured result.
        | Belum menggunakan AI untuk menyusun bahasa final.
        |
        | Nanti saat AI orchestration aktif, fungsi ini bisa digantikan
        | oleh response generator Luna tanpa mengubah flow frontend.
        |--------------------------------------------------------------------------
        */

        function buildLunaFinalResponse(
            workflow
        ) {

            const specialistResult =
                workflow?.specialist_result
                ?? null;


            if (!specialistResult) {

                return {
                    title:
                        `Selesai, ${currentUser.displayName}. ✓`,

                    message:
                        `Proses "${workflow?.title ?? 'Academic Request'}" sudah selesai.`,
                };
            }


            /*
            |--------------------------------------------------------------------------
            | Get Active Classes
            |--------------------------------------------------------------------------
            */

            if (
                specialistResult.action
                === 'get_active_classes'
            ) {

                const result =
                    specialistResult.result
                    ?? {};


                const classes =
                    Array.isArray(
                        result.data
                    )
                        ? result.data
                        : [];


                const total =
                    Number(
                        result.meta?.count
                        ?? classes.length
                    );


                if (total === 0) {

                    return {
                        title:
                            `Berikut hasilnya, ${currentUser.displayName}.`,

                        message:
                            'Raka tidak menemukan kelas yang berstatus ongoing di FlexOps.',
                    };
                }


                const classNames =
                    classes
                        .map(
                            item =>
                                item?.name
                                ?? null
                        )
                        .filter(Boolean);


                const visibleClassNames =
                    classNames.slice(
                        0,
                        5
                    );


                let classSummary =
                    visibleClassNames.join(
                        ', '
                    );


                if (
                    classNames.length > 5
                ) {

                    classSummary +=
                        `, dan ${
                            classNames.length - 5
                        } lainnya`;
                }


                return {
                    title:
                        `Berikut hasilnya, ${currentUser.displayName}.`,

                    message:
                        classSummary
                            ? `Raka menemukan ${total} kelas berstatus ongoing di FlexOps: ${classSummary}.`
                            : `Raka menemukan ${total} kelas berstatus ongoing di FlexOps.`,
                };
            }


            /*
            |--------------------------------------------------------------------------
            | Generic Specialist Result
            |--------------------------------------------------------------------------
            */

            return {
                title:
                    `Berikut hasilnya, ${currentUser.displayName}.`,

                message:
                    `${specialistResult.agent?.name ?? 'Specialist'} sudah menyelesaikan pekerjaannya dan hasilnya sudah saya tinjau.`,
            };
        }


        /*
        |--------------------------------------------------------------------------
        | Play Workflow Handoff
        |--------------------------------------------------------------------------
        |
        | Flow visual:
        |
        | Luna
        | → menjelaskan delegasi
        | → specialist bekerja
        | → specialist selesai & return result
        | → kembali ke Luna
        | → Luna menutup workflow
        |
        | Specialist tidak menyampaikan final answer ke user.
        |--------------------------------------------------------------------------
        */

        async function playWorkflowHandoff(
            workflow
        ) {

            if (!workflow) {
                return;
            }


            const handoff =
                workflow.handoff
                ?? null;


            const executionAgent =
                workflow.execution_agent
                ?? null;


            const finalAgent =
                workflow.active_agent
                ?? primaryAgent
                ?? null;


            /*
            |--------------------------------------------------------------------------
            | No Specialist Handoff
            |--------------------------------------------------------------------------
            */

            if (
                !handoff
                ||
                !executionAgent
                ||
                !finalAgent
            ) {

                await applyWorkflowFinalAgent(
                    workflow
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Preload Specialist + Final Agent
            |--------------------------------------------------------------------------
            */

            await Promise.all([
                preloadAgentAvatars(
                    executionAgent.avatars
                    ?? {}
                ),

                preloadAgentAvatars(
                    finalAgent.avatars
                    ?? {}
                ),
            ]);


            /*
            |--------------------------------------------------------------------------
            | 1. Luna Explains Delegation
            |--------------------------------------------------------------------------
            */

            await setLunaState(
                'explaining',
                {
                    title:
                        `Saya akan menghubungi ${executionAgent.name}.`,

                    message:
                        handoff.message
                            ? `${handoff.message} Setelah selesai, ${executionAgent.name} akan mengembalikan hasilnya kepada saya.`
                            : `${executionAgent.name} akan membantu mengerjakan proses ini. Setelah selesai, hasilnya akan dikembalikan kepada saya.`,
                }
            );


            await waitForPresentation(
                handoffPresentationTiming.delegation
            );


            /*
            |--------------------------------------------------------------------------
            | 2. Specialist Working
            |--------------------------------------------------------------------------
            */

            await setMainAgentState(
                executionAgent,
                'working',
                {
                    title:
                        `${executionAgent.name} sedang bekerja.`,

                    message:
                        `${executionAgent.name} sedang mengerjakan "${workflow.title ?? 'Academic Request'}".`,
                }
            );


            await waitForPresentation(
                handoffPresentationTiming.specialistWorking
            );


            /*
            |--------------------------------------------------------------------------
            | 3. Specialist Completed
            |--------------------------------------------------------------------------
            |
            | Specialist hanya mengembalikan hasil ke Luna.
            | Bukan memberikan final explanation ke user.
            |--------------------------------------------------------------------------
            */

            await setMainAgentState(
                executionAgent,
                'completed',
                {
                    title:
                        `${executionAgent.name} selesai. ✓`,

                    message:
                        `Pekerjaan sudah selesai. Hasilnya dikembalikan ke ${finalAgent.name ?? 'Luna'} untuk ditinjau.`,
                }
            );


            await waitForPresentation(
                handoffPresentationTiming.specialistCompleted
            );


            /*
            |--------------------------------------------------------------------------
            | 4. Return To Luna
            |--------------------------------------------------------------------------
            */

            await setMainAgentState(
                finalAgent,
                'explaining',
                {
                    title:
                        `${executionAgent.name} sudah kembali dengan hasilnya.`,

                    message:
                        `Hasil dari ${executionAgent.name} sudah saya terima. Saya sedang meninjaunya sebelum menyampaikannya kepada ${currentUser.displayName}.`,
                }
            );


            await waitForPresentation(
                handoffPresentationTiming.returnToLuna
            );


            /*
            |--------------------------------------------------------------------------
            | 5. Luna Explains Final Result
            |--------------------------------------------------------------------------
            |
            | Specialist hanya mengembalikan structured result.
            | Luna yang menyampaikan hasil final ke user.
            |--------------------------------------------------------------------------
            */

            const finalResponse =
                buildLunaFinalResponse(
                    workflow
                );


            await setMainAgentState(
                finalAgent,
                workflow.active_state
                    ?? 'completed',
                finalResponse
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Refresh Icons
        |--------------------------------------------------------------------------
        */

        function refreshIcons() {

            if (
                typeof window.renderLucideIcons
                === 'function'
            ) {

                window.renderLucideIcons();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Load Office State
        |--------------------------------------------------------------------------
        */

        async function loadOfficeState() {

            try {

                const response =
                    await fetch(
                        stateUrl,
                        {
                            method:
                                'GET',

                            headers: {
                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },

                            credentials:
                                'same-origin',
                        }
                    );


                if (!response.ok) {

                    throw new Error(
                        `Failed to load office state: ${response.status}`
                    );
                }


                const data =
                    await response.json();


                if (!data.success) {

                    throw new Error(
                        'Academic AI Office state response is invalid.'
                    );
                }


                await renderOfficeState(
                    data
                );


                officeReady =
                    true;


                return data;

            } catch (error) {

                officeReady =
                    false;


                console.error(
                    'Academic AI Office state error:',
                    error
                );


                throw error;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Chat Loading
        |--------------------------------------------------------------------------
        */

        function setChatLoading(
            isLoading
        ) {

            if (
                !submitButton
                ||
                !messageInput
            ) {
                return;
            }


            submitButton.disabled =
                isLoading;

            messageInput.disabled =
                isLoading;


            submitButton.classList.toggle(
                'opacity-60',
                isLoading
            );


            submitButton.classList.toggle(
                'cursor-not-allowed',
                isLoading
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Submit Message
        |--------------------------------------------------------------------------
        */

        async function submitMessage(
            message
        ) {

            const payload = {
                message: message,
            };


            const conversationId =
                getConversationId();


            if (conversationId) {

                payload.conversation_id =
                    conversationId;
            }


            const response =
                await fetch(
                    messageUrl,
                    {
                        method:
                            'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'Content-Type':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                csrfToken,
                        },

                        credentials:
                            'same-origin',

                        body:
                            JSON.stringify(
                                payload
                            ),
                    }
                );


            if (
                response.status
                === 422
            ) {

                const errorData =
                    await response.json();


                const firstError =
                    Object.values(
                        errorData.errors
                        ?? {}
                    )[0]?.[0];


                throw new Error(
                    firstError
                    ?? 'Pesan tidak valid.'
                );
            }


            if (!response.ok) {

                throw new Error(
                    `Gagal mengirim pesan. HTTP ${response.status}`
                );
            }


            return response.json();
        }


        /*
        |--------------------------------------------------------------------------
        | Chat Form
        |--------------------------------------------------------------------------
        */

        chatForm?.addEventListener(
            'submit',

            async function (event) {

                event.preventDefault();


                const message =
                    messageInput
                        .value
                        .trim();


                if (!message) {

                    messageInput.focus();

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Ensure Office Ready
                |--------------------------------------------------------------------------
                */

                if (!officeReady) {

                    try {

                        setChatLoading(
                            true
                        );


                        await loadOfficeState();

                    } catch (error) {

                        alert(
                            'Academic AI Office belum siap. Silakan coba kembali.'
                        );


                        setChatLoading(
                            false
                        );


                        return;
                    }
                }


                setChatLoading(
                    true
                );


                try {

                    /*
                    |--------------------------------------------------------------------------
                    | Dynamic Conversation Copy
                    |--------------------------------------------------------------------------
                    */

                    const conversationCopy =
                        getConversationStateCopy(
                            message
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Listening
                    |--------------------------------------------------------------------------
                    |
                    | Setiap message baru — termasuk chat kedua, ketiga, dst —
                    | selalu memulai conversational state dari Luna.
                    |--------------------------------------------------------------------------
                    */

                    await setLunaState(
                        'listening',
                        conversationCopy.listening
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Start Request Immediately
                    |--------------------------------------------------------------------------
                    |
                    | Backend mulai diproses tanpa menunggu presentation timing.
                    | Jadi perubahan avatar tidak menambah latency AI secara berarti.
                    |--------------------------------------------------------------------------
                    */

                    const messageRequest =
                        submitMessage(
                            message
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Keep Listening Visible Briefly
                    |--------------------------------------------------------------------------
                    */

                    await waitForPresentation(
                        conversationPresentationTiming.listeningMinimum
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Thinking
                    |--------------------------------------------------------------------------
                    |
                    | State visual tetap thinking, tetapi copy menyesuaikan
                    | percakapan. "Terima kasih" tidak lagi dipresentasikan
                    | seperti sebuah operational task.
                    |--------------------------------------------------------------------------
                    */

                    await setLunaState(
                        'thinking',
                        conversationCopy.thinking
                    );


                    const thinkingMinimum =
                        waitForPresentation(
                            conversationPresentationTiming.thinkingMinimum
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Await Backend + Minimum Thinking Presentation
                    |--------------------------------------------------------------------------
                    */

                    const [
                        data,
                    ] =
                        await Promise.all([
                            messageRequest,
                            thinkingMinimum,
                        ]);


                    if (!data.success) {

                        throw new Error(
                            'Response message tidak valid.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Conversation
                    |--------------------------------------------------------------------------
                    */

                    if (
                        data.conversation?.id
                    ) {

                        setConversationId(
                            data.conversation.id
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Workflow
                    |--------------------------------------------------------------------------
                    */

                    if (data.workflow) {

                        renderWorkflow(
                            data.workflow
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | User-Facing Luna Response
                    |--------------------------------------------------------------------------
                    |
                    | Prioritas:
                    |
                    | 1. Kalau backend sudah mengirim assistant_message,
                    |    tampilkan jawaban natural Luna.
                    |
                    | 2. Kalau belum ada assistant_message (contoh:
                    |    specialist result yang masih deterministic),
                    |    lanjutkan visual handoff workflow seperti sebelumnya.
                    |--------------------------------------------------------------------------
                    */

                    const hasAssistantMessage =
                        await renderAssistantMessage(
                            data.assistant_message
                        );


                    if (
                        !hasAssistantMessage
                        &&
                        data.workflow
                    ) {

                        await playWorkflowHandoff(
                            data.workflow
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Clear Input
                    |--------------------------------------------------------------------------
                    */

                    messageInput.value =
                        '';


                    messageInput.focus();


                    console.log(
                        'Academic AI Office message saved:',
                        data
                    );

                } catch (error) {

                    console.error(
                        'Academic AI Office message error:',
                        error
                    );


                    await setLunaState(
                        'questioning',
                        {
                            message:
                                'Permintaan belum berhasil diproses. Silakan periksa kembali atau coba lagi.',
                        }
                    );


                    alert(
                        error.message
                        ?? 'Pesan gagal dikirim.'
                    );

                } finally {

                    setChatLoading(
                        false
                    );
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Init
        |--------------------------------------------------------------------------
        */

        async function initAcademicAiOffice() {

            setChatLoading(
                true
            );


            try {

                await loadOfficeState();

            } catch (error) {

                console.error(
                    'Academic AI Office initialization failed:',
                    error
                );

            } finally {

                setChatLoading(
                    false
                );
            }
        }


        initAcademicAiOffice();

    });
</script>

@endpush