<?php

use Livewire\Component;
use App\Models\StravaActivity;
use App\Models\UserTire;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public string $username = '';
    public int $year = 2026;
    public string $title = 'MON RÉCAP';
    public float $distance = 0.0;
    public float $elevation = 0.0;
    public int $ridesCount = 0;
    public string $tireModel = '';
    public string $style = 'michelin-blue';
    public string $highlightMetric = 'distance';

    /**
     * Initialize component and load stats from database.
     */
    public function mount(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->username = '@' . strtolower(str_replace(' ', '', $user->name));
            $this->year = intval(now()->format('Y'));

            // Load user activities
            $query = $user->stravaActivities();
            $hasActivitiesThisYear = (clone $query)->whereYear('start_date', $this->year)->exists();

            if (!$hasActivitiesThisYear) {
                $latestActivity = (clone $query)->orderBy('start_date', 'desc')->first();
                if ($latestActivity) {
                    $this->year = intval($latestActivity->start_date->format('Y'));
                }
            }

            $yearQuery = (clone $query)->whereYear('start_date', $this->year);

            $this->distance = round($yearQuery->sum('distance_m') / 1000);
            $this->elevation = round($yearQuery->sum('total_elevation_gain_m'));
            $this->ridesCount = $yearQuery->count();

            // Load active tire model
            $activeTire = UserTire::where('user_id', $user->id)
                ->where('is_active', true)
                ->with('product')
                ->first();

            if ($activeTire && $activeTire->product) {
                $this->tireModel = $activeTire->product->web_range_name;
            } else {
                $this->tireModel = 'Power Gravel';
            }

            // Fallback mock stats if the user has 0 activities
            if ($this->ridesCount === 0) {
                $this->distance = 3840;
                $this->elevation = 42100;
                $this->ridesCount = 82;
                $this->tireModel = 'Power Gravel';
            }
        } else {
            $this->username = '@rider';
            $this->distance = 3840;
            $this->elevation = 42100;
            $this->ridesCount = 82;
            $this->tireModel = 'Power Gravel';
        }
    }
}; ?>

<div x-data="{
    style: @entangle('style'),
    username: @entangle('username'),
    title: @entangle('title'),
    distance: @entangle('distance'),
    elevation: @entangle('elevation'),
    ridesCount: @entangle('ridesCount'),
    tireModel: @entangle('tireModel'),
    highlightMetric: @entangle('highlightMetric'),
    year: @entangle('year'),

    downloading: false,

    generateImage() {
        this.downloading = true;

        setTimeout(() => {
            try {
                const canvas = document.getElementById('instagram-canvas');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                // Clear
                ctx.clearRect(0, 0, 1080, 1080);

                // Background
                if (this.style === 'michelin-blue') {
                    const grad = ctx.createLinearGradient(0, 0, 1080, 1080);
                    grad.addColorStop(0, '#27509b');
                    grad.addColorStop(1, '#0D0D0D');
                    ctx.fillStyle = grad;
                    ctx.fillRect(0, 0, 1080, 1080);

                    ctx.strokeStyle = 'rgba(252, 229, 0, 0.08)';
                    ctx.lineWidth = 16;
                    for (let i = -500; i < 1580; i += 120) {
                        ctx.beginPath();
                        ctx.moveTo(i, 0);
                        ctx.lineTo(i + 400, 1080);
                        ctx.stroke();
                    }
                } else if (this.style === 'michelin-yellow') {
                    ctx.fillStyle = '#FCE500';
                    ctx.fillRect(0, 0, 1080, 1080);

                    ctx.fillStyle = 'rgba(39, 80, 155, 0.05)';
                    for (let x = 30; x < 1080; x += 60) {
                        for (let y = 30; y < 1080; y += 60) {
                            ctx.beginPath();
                            ctx.arc(x, y, 4, 0, Math.PI * 2);
                            ctx.fill();
                        }
                    }
                } else if (this.style === 'dark-carbon') {
                    const grad = ctx.createLinearGradient(0, 0, 1080, 1080);
                    grad.addColorStop(0, '#1A1A1A');
                    grad.addColorStop(1, '#0D0D0D');
                    ctx.fillStyle = grad;
                    ctx.fillRect(0, 0, 1080, 1080);

                    ctx.strokeStyle = 'rgba(255, 255, 255, 0.03)';
                    ctx.lineWidth = 2;
                    for (let i = 0; i < 1080; i += 40) {
                        ctx.beginPath(); ctx.moveTo(0, i); ctx.lineTo(1080, i); ctx.stroke();
                        ctx.beginPath(); ctx.moveTo(i, 0); ctx.lineTo(i, 1080); ctx.stroke();
                    }
                } else if (this.style === 'emerald-adventure') {
                    const grad = ctx.createLinearGradient(0, 0, 1080, 1080);
                    grad.addColorStop(0, '#2E7D32');
                    grad.addColorStop(1, '#0d0d0d');
                    ctx.fillStyle = grad;
                    ctx.fillRect(0, 0, 1080, 1080);

                    ctx.strokeStyle = 'rgba(146, 193, 146, 0.08)';
                    ctx.lineWidth = 4;
                    for (let r = 200; r < 1200; r += 200) {
                        ctx.beginPath();
                        ctx.arc(1080, 0, r, 0, Math.PI * 2);
                        ctx.stroke();
                    }
                }

                // Big Year watermark
                ctx.textAlign = 'left';
                ctx.fillStyle = this.style === 'michelin-yellow' ? 'rgba(39, 80, 155, 0.07)' : 'rgba(255, 255, 255, 0.04)';
                ctx.font = '900 340px sans-serif';
                ctx.fillText(this.year.toString(), 80, 480);

                const logoImg = document.getElementById('michelin-logo-canvas');
                const bibendumImg = document.getElementById('michelin-bibendum-canvas');

                // Michelin Typography Logo
                if (this.style === 'michelin-yellow') {
                    ctx.drawImage(logoImg, 80, 80, 210, 56);
                } else {
                    ctx.fillStyle = '#FFFFFF';
                    ctx.beginPath();
                    ctx.roundRect(80, 78, 230, 60, 6);
                    ctx.fill();
                    ctx.drawImage(logoImg, 90, 84, 210, 48);
                }

                // Header Title
                ctx.textAlign = 'right';
                ctx.fillStyle = this.style === 'michelin-yellow' ? '#27509b' : '#FFFFFF';
                ctx.font = '800 24px sans-serif';
                ctx.fillText(this.title.toUpperCase(), 1000, 118);

                // Main Metric in Center
                ctx.textAlign = 'center';
                let mainVal = '';
                let mainLabel = '';
                if (this.highlightMetric === 'distance') {
                    mainVal = Number(this.distance).toLocaleString('fr-FR') + ' KM';
                    mainLabel = 'DISTANCE PARCOURUE';
                } else if (this.highlightMetric === 'elevation') {
                    mainVal = Number(this.elevation).toLocaleString('fr-FR') + ' M';
                    mainLabel = 'DÉNIVELÉ POSITIF';
                } else {
                    mainVal = this.ridesCount + ' SORTIES';
                    mainLabel = 'ACTIVITÉS ENREGISTRÉES';
                }

                ctx.fillStyle = this.style === 'michelin-yellow' ? 'rgba(39, 80, 155, 0.65)' : 'rgba(255, 255, 255, 0.6)';
                ctx.font = '800 20px sans-serif';
                ctx.fillText(mainLabel, 540, 400);

                ctx.fillStyle = this.style === 'michelin-yellow' ? '#27509b' : '#FFFFFF';
                ctx.font = '900 110px sans-serif';
                ctx.fillText(mainVal, 540, 510);

                ctx.fillStyle = this.style === 'michelin-yellow' ? '#27509b' : '#FCE500';
                ctx.fillRect(440, 550, 200, 8);

                // Secondary Metrics Grid
                ctx.textAlign = 'left';

                // Column 1
                let c1Val = '', c1Label = '';
                if (this.highlightMetric === 'distance') {
                    c1Val = Number(this.elevation).toLocaleString('fr-FR') + ' m';
                    c1Label = 'Dénivelé';
                } else {
                    c1Val = Number(this.distance).toLocaleString('fr-FR') + ' km';
                    c1Label = 'Distance';
                }
                ctx.fillStyle = this.style === 'michelin-yellow' ? 'rgba(39, 80, 155, 0.6)' : 'rgba(255, 255, 255, 0.5)';
                ctx.font = 'bold 16px sans-serif';
                ctx.fillText(c1Label.toUpperCase(), 120, 720);
                ctx.fillStyle = this.style === 'michelin-yellow' ? '#27509b' : '#FFFFFF';
                ctx.font = '900 42px sans-serif';
                ctx.fillText(c1Val, 120, 775);

                // Column 2
                let c2Val = '', c2Label = '';
                if (this.highlightMetric === 'rides') {
                    c2Val = Number(this.elevation).toLocaleString('fr-FR') + ' m';
                    c2Label = 'Dénivelé';
                } else {
                    c2Val = this.ridesCount + ' sorties';
                    c2Label = 'Sorties';
                }
                ctx.fillStyle = this.style === 'michelin-yellow' ? 'rgba(39, 80, 155, 0.6)' : 'rgba(255, 255, 255, 0.5)';
                ctx.font = 'bold 16px sans-serif';
                ctx.fillText(c2Label.toUpperCase(), 460, 720);
                ctx.fillStyle = this.style === 'michelin-yellow' ? '#27509b' : '#FFFFFF';
                ctx.font = '900 42px sans-serif';
                ctx.fillText(c2Val, 460, 775);

                // Column 3
                ctx.fillStyle = this.style === 'michelin-yellow' ? 'rgba(39, 80, 155, 0.6)' : 'rgba(255, 255, 255, 0.5)';
                ctx.font = 'bold 16px sans-serif';
                ctx.fillText('ÉQUIPEMENT', 780, 720);
                ctx.fillStyle = this.style === 'michelin-yellow' ? '#27509b' : '#FFFFFF';
                ctx.font = '900 30px sans-serif';
                ctx.fillText(this.tireModel, 780, 775);

                // Footer Divider
                ctx.strokeStyle = this.style === 'michelin-yellow' ? 'rgba(39, 80, 155, 0.2)' : 'rgba(255, 255, 255, 0.1)';
                ctx.beginPath();
                ctx.moveTo(80, 860);
                ctx.lineTo(1000, 860);
                ctx.stroke();

                // User Capsule
                ctx.fillStyle = this.style === 'michelin-yellow' ? '#27509b' : 'rgba(255, 255, 255, 0.1)';
                const textWidth = ctx.measureText(this.username).width;
                const capW = Math.max(textWidth + 50, 180);
                ctx.beginPath();
                ctx.roundRect(80, 900, capW, 54, 27);
                ctx.fill();

                ctx.fillStyle = this.style === 'michelin-yellow' ? '#FFFFFF' : '#FCE500';
                ctx.font = 'bold 22px sans-serif';
                ctx.fillText(this.username, 105, 935);

                // Footer tagline
                ctx.textAlign = 'right';
                ctx.fillStyle = this.style === 'michelin-yellow' ? 'rgba(39, 80, 155, 0.7)' : 'rgba(255, 255, 255, 0.8)';
                ctx.font = 'bold 15px sans-serif';
                ctx.fillText('POWERED BY', 915, 933);

                // Draw Bibendum Mascot next to tagline
                if (this.style !== 'michelin-yellow') {
                    ctx.filter = 'brightness(0) invert(1)';
                }
                ctx.drawImage(bibendumImg, 925, 890, 75, 75);
                ctx.filter = 'none';

                // Download action
                const dataUrl = canvas.toDataURL('image/png');
                const link = document.createElement('a');
                link.download = `michelin_recap_${this.year}_${this.username.replace('@', '')}.png`;
                link.href = dataUrl;
                link.click();
            } catch (err) {
                console.error('Error generating image:', err);
            } finally {
                this.downloading = false;
            }
        }, 300);
    }
}"
class="grid grid-cols-1 xl:grid-cols-5 gap-8 items-start w-full">
    <!-- Preview Box (Left Column, span 2) -->
    <div class="xl:col-span-2 flex flex-col gap-3">
        <flux:label class="text-zinc-650 dark:text-zinc-400 font-semibold">{{ __('Aperçu de votre post (Format Carré 1:1)') }}</flux:label>

        <!-- Interactive Preview -->
        <div class="w-full aspect-square rounded-2xl shadow-xl border overflow-hidden relative transition-all duration-300 hover:scale-[1.01]"
             :class="{
                'bg-gradient-to-br from-michelin-blue to-gray-dark-90 border-michelin-blue/20 text-white': style === 'michelin-blue',
                'bg-michelin-yellow border-michelin-yellow-dark-03 text-michelin-blue': style === 'michelin-yellow',
                'bg-gradient-to-br from-gray-dark-80 to-gray-dark-90 border-gray-dark-70/40 text-white': style === 'dark-carbon',
                'bg-gradient-to-br from-valide to-gray-dark-90 border-valide/20 text-white': style === 'emerald-adventure'
             }">

            <!-- Background Decorative Stripes / Grids -->
            <template x-if="style === 'michelin-blue'">
                <div class="absolute inset-0 opacity-10 pointer-events-none overflow-hidden">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_bottom_right,var(--color-michelin-yellow)_0%,transparent_50%)]"></div>
                </div>
            </template>
            <template x-if="style === 'emerald-adventure'">
                <div class="absolute inset-0 opacity-10 pointer-events-none overflow-hidden bg-[radial-gradient(circle_at_top_right,var(--color-valid-dark)_0%,transparent_60%)]">
                </div>
            </template>
            <template x-if="style === 'dark-carbon'">
                <div class="absolute inset-0 opacity-15 pointer-events-none overflow-hidden"
                     style="background-image: linear-gradient(0deg, transparent 24%, rgba(255, 255, 255, .05) 25%, rgba(255, 255, 255, .05) 26%, transparent 27%, transparent 74%, rgba(255, 255, 255, .05) 75%, rgba(255, 255, 255, .05) 76%, transparent 77%, transparent), linear-gradient(90deg, transparent 24%, rgba(255, 255, 255, .05) 25%, rgba(255, 255, 255, .05) 26%, transparent 27%, transparent 74%, rgba(255, 255, 255, .05) 75%, rgba(255, 255, 255, .05) 76%, transparent 77%, transparent); background-size: 30px 30px;">
                </div>
            </template>

            <!-- Large watermark Year in background -->
            <div class="absolute top-1/4 left-6 text-[15rem] font-black leading-none pointer-events-none select-none tracking-tighter"
                 :class="{
                    'text-white/[0.04]': style !== 'michelin-yellow',
                    'text-michelin-blue/[0.06]': style === 'michelin-yellow'
                 }"
                 x-text="year">
            </div>

            <!-- Card Content Wrapper -->
            <div class="absolute inset-0 p-6 flex flex-col justify-between z-10">
                <!-- Top Header Bar -->
                <div class="flex items-center justify-between">
                    <!-- Michelin Typography Logo -->
                    <div class="flex items-center">
                        <img src="{{ asset('images/michelin-logo.png') }}"
                             alt="Michelin"
                             class="h-7 w-auto object-contain select-none pointer-events-none"
                             :class="{
                                'bg-white p-1 rounded-sm shadow-xs': style !== 'michelin-yellow'
                             }">
                    </div>
                    <!-- Right text -->
                    <div class="font-extrabold text-[10px] tracking-widest uppercase opacity-85"
                         x-text="title">
                    </div>
                </div>

                <!-- Center Large Stats -->
                <div class="text-center flex flex-col items-center gap-2">
                    <span class="text-[10px] font-black uppercase tracking-widest"
                          :class="{
                             'text-michelin-blue/70': style === 'michelin-yellow',
                             'text-white/60': style !== 'michelin-yellow'
                          }"
                          x-text="highlightMetric === 'distance' ? 'Distance parcourue' : (highlightMetric === 'elevation' ? 'Dénivelé positif' : 'Sorties enregistrées')">
                    </span>

                    <h2 class="text-4xl sm:text-5xl font-black tracking-tight"
                        :class="{
                           'text-michelin-blue': style === 'michelin-yellow',
                           'text-white': style !== 'michelin-yellow'
                        }">
                        <span x-show="highlightMetric === 'distance'" x-text="Number(distance).toLocaleString('fr-FR') + ' KM'"></span>
                        <span x-show="highlightMetric === 'elevation'" x-text="Number(elevation).toLocaleString('fr-FR') + ' M'"></span>
                        <span x-show="highlightMetric === 'rides'" x-text="ridesCount + ' SORTIES'"></span>
                    </h2>

                    <div class="w-16 h-1 rounded-full mt-1"
                         :class="{
                            'bg-michelin-blue': style === 'michelin-yellow',
                            'bg-michelin-yellow': style !== 'michelin-yellow'
                         }">
                    </div>
                </div>

                <!-- Bottom Stats Grid and User Tag -->
                <div class="flex flex-col gap-4">
                    <!-- Horizontal Line -->
                    <div class="w-full h-px"
                         :class="{
                            'bg-michelin-blue/15': style === 'michelin-yellow',
                            'bg-white/10': style !== 'michelin-yellow'
                         }">
                    </div>

                    <!-- 3 Column stats -->
                    <div class="grid grid-cols-3 gap-2 text-left">
                        <!-- Stat Col 1 -->
                        <div>
                            <span class="text-[8px] font-bold uppercase tracking-wider block opacity-60"
                                  x-text="highlightMetric === 'distance' ? 'Dénivelé' : 'Distance'">
                            </span>
                            <span class="text-base font-black tracking-tight block truncate"
                                  :class="{ 'text-michelin-blue': style === 'michelin-yellow' }">
                                <span x-show="highlightMetric === 'distance'" x-text="Number(elevation).toLocaleString('fr-FR') + ' m'"></span>
                                <span x-show="highlightMetric !== 'distance'" x-text="Number(distance).toLocaleString('fr-FR') + ' km'"></span>
                            </span>
                        </div>

                        <!-- Stat Col 2 -->
                        <div>
                            <span class="text-[8px] font-bold uppercase tracking-wider block opacity-60"
                                  x-text="highlightMetric === 'rides' ? 'Dénivelé' : 'Activités'">
                            </span>
                            <span class="text-base font-black tracking-tight block truncate"
                                  :class="{ 'text-michelin-blue': style === 'michelin-yellow' }">
                                <span x-show="highlightMetric === 'rides'" x-text="Number(elevation).toLocaleString('fr-FR') + ' m'"></span>
                                <span x-show="highlightMetric !== 'rides'" x-text="ridesCount + ' sorties'"></span>
                            </span>
                        </div>

                        <!-- Stat Col 3 -->
                        <div>
                            <span class="text-[8px] font-bold uppercase tracking-wider block opacity-60">Équipement</span>
                            <span class="text-sm font-black truncate block"
                                  :class="{ 'text-michelin-blue': style === 'michelin-yellow' }"
                                  x-text="tireModel">
                            </span>
                        </div>
                    </div>

                    <!-- Separator Line -->
                    <div class="w-full h-px"
                         :class="{
                            'bg-michelin-blue/15': style === 'michelin-yellow',
                            'bg-white/10': style !== 'michelin-yellow'
                         }">
                    </div>

                    <!-- Footer Bar -->
                    <div class="flex items-center justify-between">
                        <!-- User Capsule -->
                        <div class="px-3 py-1 rounded-full text-xs font-bold truncate max-w-[150px]"
                             :class="{
                                'bg-michelin-blue text-white': style === 'michelin-yellow',
                                'bg-white/15 text-michelin-yellow': style !== 'michelin-yellow'
                             }"
                             x-text="username">
                        </div>

                        <!-- Brand Mascot and Text -->
                        <div class="flex items-center gap-1.5 opacity-80 select-none">
                            <span class="text-[8px] font-bold tracking-widest uppercase shrink-0">POWERED BY</span>
                            <img src="{{ asset('images/michelin-bibendum.png') }}"
                                 alt="Bibendum"
                                 class="h-8 w-auto object-contain pointer-events-none"
                                 :class="{ 'brightness-0 invert': style !== 'michelin-yellow' }">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image sources for Canvas rendering -->
        <img id="michelin-logo-canvas" src="{{ asset('images/michelin-logo.png') }}" class="hidden" />
        <img id="michelin-bibendum-canvas" src="{{ asset('images/michelin-bibendum.png') }}" class="hidden" />
    </div>

    <!-- Controls (Right Column, span 3) -->
    <div class="xl:col-span-3 bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/60 dark:border-zinc-800/60 rounded-2xl p-6 flex flex-col gap-6">
        <div>
            <flux:heading class="font-black tracking-tight text-zinc-800 dark:text-zinc-100">{{ __('Personnaliser mon Post') }}</flux:heading>
            <flux:subheading>{{ __('Adaptez le style et les données de votre récapitulatif pour les réseaux sociaux.') }}</flux:subheading>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Style Template Select -->
            <div class="flex flex-col gap-2">
                <flux:label>{{ __('Style du Template') }}</flux:label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="style = 'michelin-blue'"
                            class="flex items-center gap-2 p-2.5 rounded-xl border text-xs font-bold transition-all text-left cursor-pointer"
                            :class="style === 'michelin-blue' ? 'border-accent bg-accent/10 dark:bg-accent/20 text-accent dark:text-michelin-blue-light font-black shadow-xs' : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-350 hover:bg-zinc-100 dark:hover:bg-zinc-800'">
                        <span class="w-4.5 h-4.5 rounded-full bg-michelin-blue border border-white/20 shrink-0"></span>
                        Blue Classique
                    </button>
                    <button type="button" @click="style = 'michelin-yellow'"
                            class="flex items-center gap-2 p-2.5 rounded-xl border text-xs font-bold transition-all text-left cursor-pointer"
                            :class="style === 'michelin-yellow' ? 'border-accent bg-accent/10 dark:bg-accent/20 text-accent dark:text-michelin-blue-light font-black shadow-xs' : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-350 hover:bg-zinc-100 dark:hover:bg-zinc-800'">
                        <span class="w-4.5 h-4.5 rounded-full bg-michelin-yellow border border-white/20 shrink-0"></span>
                        Yellow Pop
                    </button>
                    <button type="button" @click="style = 'dark-carbon'"
                            class="flex items-center gap-2 p-2.5 rounded-xl border text-xs font-bold transition-all text-left cursor-pointer"
                            :class="style === 'dark-carbon' ? 'border-accent bg-accent/10 dark:bg-accent/20 text-accent dark:text-michelin-blue-light font-black shadow-xs' : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-350 hover:bg-zinc-100 dark:hover:bg-zinc-800'">
                        <span class="w-4.5 h-4.5 rounded-full bg-zinc-850 border border-white/20 shrink-0"></span>
                        Stealth Black
                    </button>
                    <button type="button" @click="style = 'emerald-adventure'"
                            class="flex items-center gap-2 p-2.5 rounded-xl border text-xs font-bold transition-all text-left cursor-pointer"
                            :class="style === 'emerald-adventure' ? 'border-accent bg-accent/10 dark:bg-accent/20 text-accent dark:text-michelin-blue-light font-black shadow-xs' : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-350 hover:bg-zinc-100 dark:hover:bg-zinc-800'">
                        <span class="w-4.5 h-4.5 rounded-full bg-valide border border-white/20 shrink-0"></span>
                        Wild Green
                    </button>
                </div>
            </div>

            <!-- Highlight Metric -->
            <div class="flex flex-col gap-2">
                <flux:label>{{ __('Donnée mise en avant') }}</flux:label>
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button" @click="highlightMetric = 'distance'"
                            class="p-2.5 rounded-xl border text-xs font-bold transition-all text-center cursor-pointer"
                            :class="highlightMetric === 'distance' ? 'border-accent bg-accent/10 dark:bg-accent/20 text-accent dark:text-michelin-blue-light font-black shadow-xs' : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-350 hover:bg-zinc-100 dark:hover:bg-zinc-800'">
                        Distance
                    </button>
                    <button type="button" @click="highlightMetric = 'elevation'"
                            class="p-2.5 rounded-xl border text-xs font-bold transition-all text-center cursor-pointer"
                            :class="highlightMetric === 'elevation' ? 'border-accent bg-accent/10 dark:bg-accent/20 text-accent dark:text-michelin-blue-light font-black shadow-xs' : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-350 hover:bg-zinc-100 dark:hover:bg-zinc-800'">
                        Dénivelé
                    </button>
                    <button type="button" @click="highlightMetric = 'rides'"
                            class="p-2.5 rounded-xl border text-xs font-bold transition-all text-center cursor-pointer"
                            :class="highlightMetric === 'rides' ? 'border-accent bg-accent/10 dark:bg-accent/20 text-accent dark:text-michelin-blue-light font-black shadow-xs' : 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-350 hover:bg-zinc-100 dark:hover:bg-zinc-800'">
                        Sorties
                    </button>
                </div>
            </div>
        </div>

        <div class="h-px bg-zinc-200 dark:bg-zinc-800/80"></div>

        <!-- Custom Info Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <flux:input x-model="username" label="Pseudo / Instagram Handle" type="text" />
            <flux:input x-model="title" label="Titre du post" type="text" />

            <flux:input x-model="distance" label="Distance totale (km)" type="number" />
            <flux:input x-model="elevation" label="Dénivelé total (m)" type="number" />

            <flux:input x-model="ridesCount" label="Nombre de sorties" type="number" />
            <flux:input x-model="tireModel" label="Modèle de pneu" type="text" />
        </div>

        <!-- Actions -->
        <div class="flex flex-col gap-2 mt-2">
            <flux:button variant="primary" icon="arrow-down-tray" @click="generateImage()" x-bind:disabled="downloading" class="w-full bg-accent hover:bg-accent/90 text-white cursor-pointer font-bold">
                <span x-show="!downloading">{{ __('Télécharger mon Post Instagram') }}</span>
                <span x-show="downloading">{{ __('Génération en cours...') }}</span>
            </flux:button>
            <p class="text-[10px] text-zinc-400 dark:text-zinc-500 text-center">
                {{ __('Génère une image haute résolution (1080x1080px) de votre bilan, prête à être partagée.') }}
            </p>
        </div>
    </div>
</div>
