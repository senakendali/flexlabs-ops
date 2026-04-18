<div class="card border-0 shadow-sm">
    <div class="card-body">

        {{-- HEADER --}}
        <div class="mb-4">
            <h5 class="fw-semibold mb-1">{{ $formTitle ?? 'Event Form' }}</h5>
            <small class="text-muted">
                {{ $formDescription ?? 'Fill in the marketing event information below.' }}
            </small>
        </div>

        {{-- BASIC INFO --}}
        <div class="mb-4">
            <h6 class="fw-semibold mb-3">Event Information</h6>

            <div class="row g-3">

                <div class="col-md-8">
                    <label class="form-label">Event Name *</label>
                    <input type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $event->name ?? '') }}">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Event Type *</label>
                    <select name="event_type" class="form-select">
                        <option value="">Select type</option>
                        <option value="workshop" @selected(old('event_type', $event->event_type ?? '')=='workshop')>Workshop</option>
                        <option value="webinar" @selected(old('event_type', $event->event_type ?? '')=='webinar')>Webinar</option>
                        <option value="expo" @selected(old('event_type', $event->event_type ?? '')=='expo')>Expo</option>
                        <option value="school_visit" @selected(old('event_type', $event->event_type ?? '')=='school_visit')>School Visit</option>
                        <option value="booth" @selected(old('event_type', $event->event_type ?? '')=='booth')>Booth</option>
                        <option value="community_event" @selected(old('event_type', $event->event_type ?? '')=='community_event')>Community Event</option>
                        <option value="internal_event" @selected(old('event_type', $event->event_type ?? '')=='internal_event')>Internal Event</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Event Date *</label>
                    <input type="date" name="event_date"
                        class="form-control"
                        value="{{ old('event_date', optional($event->event_date ?? null)->format('Y-m-d')) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">PIC</label>
                    <select name="pic_user_id" class="form-select">
                        <option value="">Select PIC</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                @selected(old('pic_user_id', $event->pic_user_id ?? '') == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-select">
                        <option value="draft" @selected(old('status', $event->status ?? '')=='draft')>Draft</option>
                        <option value="planned" @selected(old('status', $event->status ?? '')=='planned')>Planned</option>
                        <option value="ongoing" @selected(old('status', $event->status ?? '')=='ongoing')>Ongoing</option>
                        <option value="completed" @selected(old('status', $event->status ?? '')=='completed')>Completed</option>
                        <option value="cancelled" @selected(old('status', $event->status ?? '')=='cancelled')>Cancelled</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control"
                        value="{{ old('location', $event->location ?? '') }}">
                </div>

                <div class="col-12">
                    <label class="form-label">Target Audience</label>
                    <input type="text" name="target_audience" class="form-control"
                        value="{{ old('target_audience', $event->target_audience ?? '') }}">
                </div>

            </div>
        </div>

        {{-- METRICS --}}
        <div class="mb-4">
            <h6 class="fw-semibold mb-3">Performance Metrics</h6>

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Target Participants</label>
                    <input type="number" name="target_participants" class="form-control"
                        value="{{ old('target_participants', $event->target_participants ?? 0) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Registrants</label>
                    <input type="number" name="registrants" class="form-control"
                        value="{{ old('registrants', $event->registrants ?? 0) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Attendees</label>
                    <input type="number" name="attendees" class="form-control"
                        value="{{ old('attendees', $event->attendees ?? 0) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Leads Generated</label>
                    <input type="number" name="leads_generated" class="form-control"
                        value="{{ old('leads_generated', $event->leads_generated ?? 0) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Conversions</label>
                    <input type="number" name="conversions" class="form-control"
                        value="{{ old('conversions', $event->conversions ?? 0) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Budget</label>
                    <input type="number" step="0.01" name="budget" class="form-control"
                        value="{{ old('budget', $event->budget ?? 0) }}">
                </div>

            </div>
        </div>

        {{-- NOTES --}}
        <div class="mb-4">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="3" class="form-control">{{ old('notes', $event->notes ?? '') }}</textarea>
        </div>

        {{-- ACTIVE --}}
        <div class="form-check form-switch mb-4">
            <input class="form-check-input" type="checkbox" name="is_active"
                value="1" @checked(old('is_active', $event->is_active ?? true))>
            <label class="form-check-label">Active Event</label>
        </div>

        {{-- ACTION --}}
        <div class="d-flex justify-content-end gap-2">
            

            <a href="{{ route('marketing.events.index') }}" class="btn btn-outline-secondary">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i> {{ $submitLabel ?? 'Save Event' }}
            </button>
        </div>

    </div>
</div>