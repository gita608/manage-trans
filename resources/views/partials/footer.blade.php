            <footer class="footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            {{ date('Y') }} © {{ getSetting('app_name', config('app.name')) }}.
                        </div>
                        <div class="col-sm-6">
                            <div class="text-sm-end d-none d-sm-block">
                                {{ getSetting('app_name', config('app.name')) }}
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
