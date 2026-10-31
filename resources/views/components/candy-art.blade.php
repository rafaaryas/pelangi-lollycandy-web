@props(['kind' => 'lollipop'])
<svg {{ $attributes->class(['candy-art']) }} viewBox="0 0 180 200" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
@switch($kind)
@case('lollipop')
    <path d="M88 114 75 188" stroke="var(--candy-stick)" stroke-width="9" stroke-linecap="round"/>
    <circle cx="94" cy="67" r="54" fill="#fff8f3" stroke="var(--candy-peach)" stroke-width="3"/>
    <path d="M45 52c12-40 70-47 94-12 25 38-11 79-48 70-34-8-45-48-19-69 23-19 53 2 46 25-6 20-32 24-43 8-8-12 4-27 15-22" stroke="var(--candy-pink)" stroke-width="12" stroke-linecap="round"/>
    <path d="M50 85c-17-27 2-63 33-66M125 99c-22 22-60 6-61-21M99 43c23 8 19 36-1 40" stroke="var(--candy-blue)" stroke-width="8" stroke-linecap="round"/>
    <path d="M102 17c19 1 37 15 43 30M46 78c4 15 14 27 28 32M76 45c-14 13-11 30 4 38" stroke="var(--candy-mint)" stroke-width="6" stroke-linecap="round"/>
    <path d="M61 31c9-8 21-12 30-12" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".8"/>
@break
@case('twist')
    <path d="M91 147v43" stroke="var(--candy-stick)" stroke-width="8" stroke-linecap="round"/>
    <rect x="64" y="14" width="54" height="142" rx="27" fill="var(--candy-peach)"/>
    <path d="M70 26c11 14 30 15 46 29v22C97 60 76 58 64 43c0-6 2-12 6-17Zm-6 42c15 17 35 18 54 34v22c-18-16-39-18-54-35v-21Zm0 46c15 16 32 18 49 29-5 8-13 13-22 13-8-8-18-11-24-17-2-6-3-14-3-25Z" fill="var(--candy-pink)"/>
    <path d="M67 36c15 16 32 17 50 32M64 82c17 17 34 18 54 33M67 128c12 12 25 16 37 23" stroke="var(--candy-lavender)" stroke-width="6"/>
    <path d="M76 35v85" stroke="#fff" stroke-width="4" opacity=".5" stroke-linecap="round"/>
@break
@case('wrapped')
    <g transform="rotate(-12 90 100)">
    <path d="m48 79-35-23 6 39-9 35 40-17m83-34 35-23-6 39 9 35-40-17" fill="var(--candy-lavender)"/>
    <path d="m17 72 25 20-25 22m148-42-25 20 25 22" stroke="#fff" stroke-opacity=".55" stroke-width="4"/>
    <rect x="42" y="61" width="96" height="70" rx="30" fill="var(--candy-mint)"/>
    <path d="m63 64 22 65m3-67 24 63" stroke="#fff9ec" stroke-width="15"/>
    <path d="m50 72 17-6m40 57 15-7" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".65"/>
    </g>
@break
@case('ribbon')
    <path d="M-20 180c128 28 165-39 90-68C-7 82 6 5 140 31" stroke="var(--candy-peach)" stroke-width="28"/>
    <path d="M-20 180c128 28 165-39 90-68C-7 82 6 5 140 31" stroke="var(--candy-pink)" stroke-width="28" stroke-dasharray="12 21"/>
    <path d="M-20 170c122 28 150-31 84-50C-2 95-9 20 141 23" stroke="#fff" stroke-opacity=".55" stroke-width="3"/>
@break
@case('jellies')
    <g transform="rotate(24 71 99)">
        <rect x="30" y="66" width="80" height="56" rx="20" fill="var(--candy-pink)"/>
        <path d="m49 70 6 47m13-49 6 49m13-45 6 39" stroke="var(--candy-peach)" stroke-width="8"/>
        <path d="m40 82 8-7" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".7"/>
    </g>
    <path d="M124 120c-5-17 1-30 16-30 15 0 23 18 17 34-4 12-28 11-33-4Z" fill="var(--candy-mint)"/>
    <path d="m135 100-3 8" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".7"/>
@break
@case('sprinkles')
    <path d="m37 77 15 10" stroke="var(--candy-pink)" stroke-width="8" stroke-linecap="round"/>
    <path d="m109 42-6 18" stroke="var(--candy-blue)" stroke-width="8" stroke-linecap="round"/>
    <path d="m137 111 14-7" stroke="var(--candy-peach)" stroke-width="9" stroke-linecap="round"/>
    <path d="m78 142 5 13" stroke="var(--candy-mint)" stroke-width="6" stroke-linecap="round"/>
@break
@endswitch
</svg>
