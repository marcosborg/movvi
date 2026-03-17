@extends('layouts.admin')
@section('content')
<div class="content">
  <div class="row">
    <div class="col-lg-12">
      <div class="panel panel-default">
        <div class="panel-heading">
          Inspecao #{{ $inspection->id }}
          <span class="label label-info" style="margin-left:8px;">{{ config('inspections.status_labels.' . $inspection->status, $inspection->status) }}</span>
          <span class="label label-default" style="margin-left:4px;">Etapa atual: {{ $inspection->current_step }}</span>
        </div>
        <div class="panel-body">
          @if(session('message'))
            <div class="alert alert-success">{{ session('message') }}</div>
          @endif
          @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
          @endif

          <div class="alert alert-info">
            <strong>Viatura:</strong> {{ $inspection->vehicle->license_plate ?? '-' }} |
            <strong>Marca/Modelo:</strong> {{ $inspection->vehicle->vehicle_brand->name ?? '-' }} {{ $inspection->vehicle->vehicle_model->name ?? '' }} |
            <strong>Ano:</strong> {{ $inspection->vehicle->year ?? '-' }} |
            <strong>Tipo:</strong> {{ config('inspections.type_labels.' . $inspection->type, $inspection->type) }}
          </div>

          <p style="margin-bottom:12px;">
            <a class="btn btn-primary" href="{{ route('admin.inspections.show', $inspection->id) }}">Ver inspecao online</a>
            @if($inspection->report)
              <a class="btn btn-default" href="{{ asset('storage/' . $inspection->report->pdf_path) }}" target="_blank">Abrir PDF</a>
            @endif
          </p>

          <div class="row" style="margin-bottom:12px;">
            @foreach($steps as $stepNumber => $stepLabel)
              <div class="col-md-3" style="margin-bottom:8px;">
                <span class="label {{ $stepNumber < $inspection->current_step ? 'label-success' : ($stepNumber == $inspection->current_step ? 'label-primary' : 'label-default') }}" style="display:block;padding:8px;white-space:normal;">
                  {{ $stepNumber }}. {{ $stepLabel }}
                </span>
              </div>
            @endforeach
          </div>

          @php($activeStep = $inspection->report ? 12 : (int) $inspection->current_step)
          <div class="alert alert-warning">
            Esta visivel apenas a etapa ativa: <strong>{{ $activeStep }}. {{ $steps[$activeStep] ?? '' }}</strong>.
          </div>
          @if($activeStep > 1 && !$inspection->locked_at)
          <form method="POST" action="{{ route('admin.inspections.back-step', $inspection->id) }}" style="margin-bottom:12px;">
            @csrf
            <button class="btn btn-default" type="submit">Voltar para etapa {{ $activeStep - 1 }}</button>
          </form>
          @endif

          @if($activeStep === 1)
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa 1. Identificacao da viatura</div>
            <div class="panel-body">
              <p><strong>Matricula:</strong> {{ $inspection->vehicle->license_plate ?? '-' }}</p>
              <p><strong>Local:</strong> {{ $inspection->location_text ?? '-' }} ({{ $inspection->location_lat ?? '-' }}, {{ $inspection->location_lng ?? '-' }})</p>
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="1">
                <input type="hidden" name="action" value="complete">
                <button class="btn btn-primary" type="submit">Concluir etapa 1</button>
              </form>
            </div>
          </div>
          @endif

          @if($activeStep === 2)
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa 2. Identificacao do condutor</div>
            <div class="panel-body">
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}">
                @csrf
                <input type="hidden" name="step" value="2">
                <div class="form-group">
                  <label class="required">Condutor</label>
                  <select name="driver_id" class="form-control select2" required>
                    @foreach($drivers as $id => $name)
                      <option value="{{ $id }}" {{ (string)$inspection->driver_id === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                  </select>
                </div>
                <button class="btn btn-default" type="submit" name="action" value="save">Guardar</button>
                <button class="btn btn-primary" type="submit" name="action" value="complete">Concluir etapa 2</button>
              </form>
            </div>
          </div>
          @endif

          @if($activeStep === 3)
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa 3. Documentacao</div>
            <div class="panel-body">
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="3">
                <div class="row">
                  <div class="col-md-12">
                    @foreach([
                      'dua' => 'Documento Unico Automovel (DUA)',
                      'insurance' => 'Seguro',
                      'inspection_periodic' => 'Inspecao periodica',
                      'tvde_stickers' => 'Disticos TVDE',
                      'no_smoking_sticker' => 'Autocolante de proibicao de fumar'
                    ] as $key => $label)
                      <div style="margin-bottom:10px;">
                        <label>
                          <input type="checkbox" name="checklist[documents][{{ $key }}]" value="1" {{ !empty($checklist['documents'][$key]) ? 'checked' : '' }}>
                          {{ $label }}
                        </label>
                        <div class="drop-upload" style="margin-top:4px;">
                          <div class="drop-upload__hint">Arraste ficheiros aqui ou clique para selecionar.</div>
                          <input type="file" class="form-control" name="checklist_photos[{{ $key }}][]" multiple>
                        </div>
                        @if(!empty($checklistPhotoBySlot[$key]))
                          <small class="text-success">Foto atual: <a target="_blank" href="{{ asset('storage/' . $checklistPhotoBySlot[$key]->path) }}">ver</a></small>
                        @else
                          <small class="text-danger">Sem foto</small>
                        @endif
                      </div>
                    @endforeach
                  </div>
                </div>
                <button class="btn btn-default" type="submit" name="action" value="save">Guardar</button>
                <button class="btn btn-primary" type="submit" name="action" value="complete">Concluir etapa 3</button>
              </form>
            </div>
          </div>
          @endif

          @if($activeStep === 4)
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa 4. Estado operacional</div>
            <div class="panel-body">
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="4">
                <div class="row">
                  <div class="col-md-12 form-group">
                    @php($cleanExterior = (int) ($checklist['cleanliness']['external'] ?? 5))
                    <label>Limpeza exterior: <strong data-range-value="clean-external">{{ $cleanExterior }}</strong>/10</label>
                    <input type="range" class="form-control" min="0" max="10" step="1" name="checklist[cleanliness][external]" value="{{ $cleanExterior }}" data-range-id="clean-external">
                    @php($cleanInterior = (int) ($checklist['cleanliness']['interior'] ?? 5))
                    <label style="margin-top:16px;">Limpeza interior: <strong data-range-value="clean-interior">{{ $cleanInterior }}</strong>/10</label>
                    <input type="range" class="form-control" min="0" max="10" step="1" name="checklist[cleanliness][interior]" value="{{ $cleanInterior }}" data-range-id="clean-interior">

                    <div class="form-group" style="margin-top:16px;">
                      @php($fuelEnergy = (int) ($checklist['fuel_energy']['level'] ?? 5))
                      <label>Combustivel | energia: <strong data-range-value="fuel-energy">{{ $fuelEnergy }}</strong>/10 <small class="text-muted">(10 = cheio)</small></label>
                      <input type="range" class="form-control" min="0" max="10" step="1" name="checklist[fuel_energy][level]" value="{{ $fuelEnergy }}" data-range-id="fuel-energy">
                    </div>
                    <div class="form-group">
                      <label>Foto do combustivel | energia</label>
                      <div class="drop-upload">
                        <div class="drop-upload__hint">Arraste ficheiros aqui ou clique para selecionar.</div>
                        <input type="file" class="form-control" name="checklist_photos[fuel_energy][]" multiple>
                      </div>
                      @if(!empty($checklistPhotoBySlot['fuel_energy']))
                        <small class="text-success">Foto atual: <a target="_blank" href="{{ asset('storage/' . $checklistPhotoBySlot['fuel_energy']->path) }}">ver</a></small>
                      @else
                        <small class="text-danger">Sem foto</small>
                      @endif
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                      @php($odometerKm = (int) ($checklist['mileage']['odometer_km'] ?? 0))
                      <label>Quilometragem (km)</label>
                      <input type="number" min="0" max="2000000" step="1" class="form-control" name="checklist[mileage][odometer_km]" value="{{ $odometerKm }}">
                    </div>
                    <div class="form-group">
                      <label>Foto do odometro</label>
                      <div class="drop-upload">
                        <div class="drop-upload__hint">Arraste ficheiros aqui ou clique para selecionar.</div>
                        <input type="file" class="form-control" name="checklist_photos[odometer][]" multiple>
                      </div>
                      @if(!empty($odometerPhoto))
                        <small class="text-success">Foto atual: <a target="_blank" href="{{ asset('storage/' . $odometerPhoto->path) }}">ver</a></small>
                      @else
                        <small class="text-danger">Sem foto</small>
                      @endif
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                      @php($tireCondition = (int) ($checklist['tire_condition']['level'] ?? 5))
                      <label>Estado dos pneus: <strong data-range-value="tire-condition">{{ $tireCondition }}</strong>/10</label>
                      <input type="range" class="form-control" min="0" max="10" step="1" name="checklist[tire_condition][level]" value="{{ $tireCondition }}" data-range-id="tire-condition">
                    </div>
                    <div class="form-group">
                      <label>Fotografias dos pneus</label>
                      <div class="drop-upload">
                        <div class="drop-upload__hint">Arraste ficheiros aqui ou clique para selecionar.</div>
                        <input type="file" class="form-control" name="checklist_photos[tires][]" multiple>
                      </div>
                      @if(!empty($checklistPhotoBySlot['tires']))
                        <small class="text-success">Foto atual: <a target="_blank" href="{{ asset('storage/' . $checklistPhotoBySlot['tires']->path) }}">ver</a></small>
                      @else
                        <small class="text-danger">Sem foto</small>
                      @endif
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                      <label>Avisos no painel</label>
                      @php($panelWarningChecked = !empty($checklist['panel_warnings']['panel_warning']))
                      <label style="display:block;">
                        <input type="checkbox" name="checklist[panel_warnings][panel_warning]" value="1" {{ $panelWarningChecked ? 'checked' : '' }}>
                        Sim
                      </label>
                      <div class="drop-upload" style="margin-top:6px;">
                        <div class="drop-upload__hint">Se assinalado, anexe foto do painel com o aviso.</div>
                        <input type="file" class="form-control" name="checklist_photos[panel_warning][]" multiple>
                      </div>
                      @if(!empty($checklistPhotoBySlot['panel_warning']))
                        <small class="text-success">Foto atual: <a target="_blank" href="{{ asset('storage/' . $checklistPhotoBySlot['panel_warning']->path) }}">ver</a></small>
                      @else
                        <small class="text-danger">Sem foto</small>
                      @endif
                    </div>
                  </div>
                </div>
                <button class="btn btn-default" type="submit" name="action" value="save">Guardar</button>
                <button class="btn btn-primary" type="submit" name="action" value="complete">Concluir etapa 4</button>
              </form>
            </div>
          </div>
          @endif

          @if($activeStep === 5)
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa 5. Acessorios e extras</div>
            <div class="panel-body">
              <div class="alert alert-info" style="margin-bottom:12px;">
                Marque cada item como presente/ausente e, quando aplicavel, o estado. Se estiver presente, anexe foto.
              </div>
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="5">
                <div class="table-responsive">
                  <table class="table table-bordered table-condensed">
                    <thead>
                      <tr>
                        <th style="width:35%;">Item</th>
                        <th style="width:15%;">Presenca</th>
                        <th style="width:15%;">Estado</th>
                        <th style="width:35%;">Foto</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($accessoryItems as $key => $label)
                        @php($presenceValue = (string) ($checklist['accessories'][$key . '_present'] ?? ''))
                        @php($stateValue = (string) ($checklist['accessories'][$key . '_state'] ?? ''))
                        <tr>
                          <td><strong>{{ $label }}</strong></td>
                          <td>
                            <select class="form-control" name="checklist[accessories][{{ $key }}_present]">
                              <option value="">Selecione</option>
                              <option value="1" {{ $presenceValue === '1' ? 'selected' : '' }}>Presente</option>
                              <option value="0" {{ $presenceValue === '0' ? 'selected' : '' }}>Ausente</option>
                            </select>
                          </td>
                          <td>
                            <select class="form-control" name="checklist[accessories][{{ $key }}_state]">
                              <option value="">Nao aplicavel</option>
                              @foreach($accessoryStateOptions as $stateKey => $stateLabel)
                                <option value="{{ $stateKey }}" {{ $stateValue === $stateKey ? 'selected' : '' }}>{{ $stateLabel }}</option>
                              @endforeach
                            </select>
                          </td>
                          <td>
                            <div class="drop-upload">
                              <div class="drop-upload__hint">Arraste ficheiros aqui ou clique para selecionar.</div>
                              <input type="file" class="form-control" name="checklist_photos[{{ $key }}][]" multiple>
                            </div>
                            @if(!empty($checklistPhotoBySlot[$key]))
                              <small class="text-success">Foto atual: <a target="_blank" href="{{ asset('storage/' . $checklistPhotoBySlot[$key]->path) }}">ver</a></small>
                            @else
                              <small class="text-danger">Sem foto</small>
                            @endif
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>

                <div class="form-group">
                  <label>Outros acessorios (campo livre)</label>
                  <textarea class="form-control" rows="3" name="checklist[accessories][other_notes]" placeholder="Ex.: Suporte de telemovel, cabos extra, etc.">{{ (string) ($checklist['accessories']['other_notes'] ?? '') }}</textarea>
                </div>
                <button class="btn btn-default" type="submit" name="action" value="save">Guardar</button>
                <button class="btn btn-primary" type="submit" name="action" value="complete">Concluir etapa 5</button>
              </form>
            </div>
          </div>
          @endif

          @if($activeStep === 6)
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa 6. Fotografias exteriores (obrigatorias)</div>
            <div class="panel-body">
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="6">
                <div class="row">
                  @foreach($requiredExterior as $slot)
                    <div class="col-md-3" style="margin-bottom:12px;">
                      <div class="well" style="margin-bottom:6px;">
                        <strong>{{ $slotLabels['exterior'][$slot] ?? $slot }}</strong><br>
                        <small class="text-muted">{{ $slot }}</small><br>
                        @if(!empty($exteriorBySlot[$slot]))
                          <span class="label label-success">OK</span>
                          <a href="{{ asset('storage/' . $exteriorBySlot[$slot]->path) }}" target="_blank">ver</a>
                        @else
                          <span class="label label-danger">Em falta</span>
                        @endif
                      </div>
                      <div class="drop-upload">
                        <div class="drop-upload__hint">Arraste ficheiros aqui ou clique para selecionar.</div>
                        <input type="file" class="form-control" name="exterior_photos[{{ $slot }}][]" multiple>
                      </div>
                    </div>
                  @endforeach
                </div>
                <button class="btn btn-default" type="submit" name="action" value="save">Guardar uploads</button>
                <button class="btn btn-primary" type="submit" name="action" value="complete">Concluir etapa 6</button>
              </form>
            </div>
          </div>
          @endif

          @if($activeStep === 7)
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa 7. Fotografias interiores (obrigatorias)</div>
            <div class="panel-body">
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="7">
                <div class="row">
                  @foreach($requiredInterior as $slot)
                    <div class="col-md-3" style="margin-bottom:12px;">
                      <div class="well" style="margin-bottom:6px;">
                        <strong>{{ $slotLabels['interior'][$slot] ?? $slot }}</strong><br>
                        <small class="text-muted">{{ $slot }}</small><br>
                        @if(!empty($interiorBySlot[$slot]))
                          <span class="label label-success">OK</span>
                          <a href="{{ asset('storage/' . $interiorBySlot[$slot]->path) }}" target="_blank">ver</a>
                        @else
                          <span class="label label-danger">Em falta</span>
                        @endif
                      </div>
                      <div class="drop-upload">
                        <div class="drop-upload__hint">Arraste ficheiros aqui ou clique para selecionar.</div>
                        <input type="file" class="form-control" name="interior_photos[{{ $slot }}][]" multiple>
                      </div>
                    </div>
                  @endforeach
                </div>
                <button class="btn btn-default" type="submit" name="action" value="save">Guardar uploads</button>
                <button class="btn btn-primary" type="submit" name="action" value="complete">Concluir etapa 7</button>
              </form>
            </div>
          </div>
          @endif

          @if(in_array($activeStep, [8, 9], true))
          @php($damageScope = $activeStep === 8 ? 'exterior' : 'interior')
          @php($submittedDamages = $inspection->damages->where('scope', $damageScope)->sortByDesc('id'))
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa {{ $activeStep }}. Danos {{ $activeStep === 8 ? 'exteriores' : 'interiores' }}</div>
            <div class="panel-body">
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="{{ $activeStep }}">
                <div class="row">
                  <div class="col-md-2"><label>Local</label><select class="form-control" name="location" data-damage-location><option value="">Selecione</option>@foreach($damageLocations as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                  <div class="col-md-2"><label>Peca</label><select class="form-control" name="part" data-damage-piece><option value="">Selecione</option></select></div>
                  <div class="col-md-2"><label>Parte</label><input class="form-control" name="part_section"></div>
                  <div class="col-md-2"><label>Tipo</label><select class="form-control" name="damage_type"><option value="">Selecione</option>@foreach($damageTypes as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
                  <div class="col-md-4">
                    <label>Fotos*</label>
                    <div class="drop-upload">
                      <div class="drop-upload__hint">Arraste ficheiros aqui ou clique para selecionar.</div>
                      <input type="file" class="form-control" name="damage_photo[]" multiple>
                    </div>
                  </div>
                </div>
                <div class="form-group" style="margin-top:8px;">
                  <label>Observacoes</label>
                  <textarea class="form-control" name="damage_notes"></textarea>
                </div>
                <button class="btn btn-default" type="submit" name="action" value="save">Guardar dano</button>
                <button class="btn btn-primary" type="submit" name="action" value="complete">Concluir etapa {{ $activeStep }}</button>
              </form>

              <hr>
              <h5 style="margin-top:0;">Danos ja submetidos ({{ $submittedDamages->count() }})</h5>
              <table class="table table-bordered table-condensed">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Local</th>
                    <th>Peca</th>
                    <th>Tipo</th>
                    <th>Foto</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($submittedDamages as $damage)
                    @php($firstPhoto = $damage->photos->first())
                    <tr>
                      <td>{{ $damage->id }}</td>
                      <td>{{ $damageLocations[$damage->location] ?? $damage->location }}</td>
                      <td>{{ $damage->part }}{{ $damage->part_section ? ' / ' . $damage->part_section : '' }}</td>
                      <td>{{ $damageTypes[$damage->damage_type] ?? $damage->damage_type }}</td>
                      <td>
                        @if($firstPhoto)
                          <a href="{{ asset('storage/' . $firstPhoto->path) }}" target="_blank">ver foto</a>
                        @else
                          -
                        @endif
                      </td>
                      <td>{!! $damage->is_resolved ? '<span class="label label-success">Resolvido</span>' : '<span class="label label-warning">Aberto</span>' !!}</td>
                    </tr>
                  @empty
                    <tr><td colspan="6" class="text-center">Ainda nao existem danos submetidos nesta etapa.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
          @endif

          @if($activeStep === 10)
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa 10. Extras e observacoes</div>
            <div class="panel-body">
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="10">
                <div class="form-group">
                  <label>Observacoes adicionais</label>
                  <textarea class="form-control" name="extra_observations">{{ $inspection->extra_observations }}</textarea>
                </div>
                <div class="form-group">
                  <label>Fotos extra (opcional)</label>
                  <div class="drop-upload">
                    <div class="drop-upload__hint">Arraste ficheiros aqui ou clique para selecionar.</div>
                    <input class="form-control" type="file" name="extra_photos[]" multiple>
                  </div>
                </div>
                <button class="btn btn-default" type="submit" name="action" value="save">Guardar</button>
                <button class="btn btn-primary" type="submit" name="action" value="complete">Concluir etapa 10</button>
              </form>
            </div>
          </div>
          @endif

          @if($activeStep === 11)
          <div class="panel panel-primary">
            <div class="panel-heading">Etapa 11. Assinaturas digitais</div>
            <div class="panel-body">
              <form method="POST" action="{{ route('admin.inspections.update-step', $inspection->id) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="step" value="11">
                <div class="row">
                  <div class="col-md-6">
                    <label>Nome de quem faz a vistoria (responsavel)</label>
                    <input class="form-control" name="inspector_name" value="{{ $signatureNames['responsible'] ?? '' }}">
                    <label style="margin-top:10px;">Assinatura do responsavel (dedo/caneta)</label>
                    <div class="signature-pad" data-signature-wrapper data-input-id="inspector_signature_data">
                      <canvas class="signature-pad__canvas" data-signature-canvas></canvas>
                      <div class="signature-pad__actions">
                        <button type="button" class="btn btn-xs btn-default" data-signature-clear>Limpar assinatura</button>
                      </div>
                    </div>
                    <input type="hidden" name="inspector_signature_data" id="inspector_signature_data">
                  </div>
                  <div class="col-md-6">
                    <label>Nome do condutor</label>
                    <input class="form-control" name="driver_signature_name" value="{{ $signatureNames['driver'] ?? '' }}">
                    <label style="margin-top:10px;">Assinatura do condutor (dedo/caneta)</label>
                    <div class="signature-pad" data-signature-wrapper data-input-id="driver_signature_data">
                      <canvas class="signature-pad__canvas" data-signature-canvas></canvas>
                      <div class="signature-pad__actions">
                        <button type="button" class="btn btn-xs btn-default" data-signature-clear>Limpar assinatura</button>
                      </div>
                    </div>
                    <input type="hidden" name="driver_signature_data" id="driver_signature_data">
                  </div>
                </div>
                <div style="margin-top:10px;">
                  <button class="btn btn-default" type="submit" name="action" value="save">Guardar assinatura</button>
                  <button class="btn btn-primary" type="submit" name="action" value="complete">Concluir etapa 11</button>
                </div>
              </form>
            </div>
          </div>
          @endif

          @if($activeStep === 12)
          <div class="panel panel-success">
            <div class="panel-heading">Etapa 12. Fecho e PDF imutavel</div>
            <div class="panel-body">
              <h4>Relatorio de pendencias</h4>
              @if(!empty($missingItems))
                <p>Itens nao preenchidos:</p>
                <div style="margin-bottom:12px;">
                  @foreach($missingItems as $m)
                    <span class="label label-danger" style="display:inline-block;margin:2px 4px 2px 0;">{{ $m['group'] }}: {{ $m['item'] }}</span>
                  @endforeach
                </div>
              @else
                <span class="label label-success">Sem pendencias</span>
              @endif

              <hr>
              @if($inspection->report)
                <p><strong>Relatorio ja gerado:</strong> <a target="_blank" href="{{ asset('storage/' . $inspection->report->pdf_path) }}">Abrir PDF</a></p>
                <p><strong>Hash:</strong> <code>{{ $inspection->report->pdf_hash }}</code></p>
              @else
                <form method="POST" action="{{ route('admin.inspections.close', $inspection->id) }}">
                  @csrf
                  <button class="btn btn-success" type="submit">Fechar inspecao e gerar PDF</button>
                </form>
              @endif
            </div>
          </div>
          @endif

        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('styles')
@parent
<style>
  .drop-upload {
    border: 2px dashed #cfd8dc;
    border-radius: 6px;
    padding: 8px;
    background: #fafafa;
    cursor: pointer;
  }
  .drop-upload.is-dragover {
    border-color: #3c8dbc;
    background: #eef7fc;
  }
  .drop-upload__hint {
    font-size: 12px;
    color: #607d8b;
    margin-bottom: 6px;
  }
  .signature-pad {
    border: 1px solid #cfd8dc;
    border-radius: 6px;
    background: #fff;
    margin-top: 6px;
    padding: 8px;
  }
  .signature-pad__canvas {
    width: 100%;
    height: 180px;
    border: 1px dashed #b0bec5;
    border-radius: 4px;
    touch-action: none;
    cursor: crosshair;
  }
  .signature-pad__actions {
    margin-top: 8px;
  }
</style>
@endsection

@section('scripts')
@parent
<script>
(function () {
  var damageParts = @json($damageParts ?? []);

  function populateDamagePieces() {
    var locationSelect = document.querySelector('[data-damage-location]');
    var pieceSelect = document.querySelector('[data-damage-piece]');
    if (!locationSelect || !pieceSelect) return;

    var selectedLocation = locationSelect.value || '';
    var pieces = (damageParts[selectedLocation] && damageParts[selectedLocation].sections) || {};
    var currentValue = pieceSelect.value || '';

    pieceSelect.innerHTML = '';

    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = 'Selecione';
    pieceSelect.appendChild(placeholder);

    Object.keys(pieces).forEach(function (key) {
      var option = document.createElement('option');
      option.value = key;
      option.textContent = pieces[key];
      if (currentValue === key) {
        option.selected = true;
      }
      pieceSelect.appendChild(option);
    });
  }

  function asFiles(fileList) {
    if (!fileList || !fileList.length) return [];
    return Array.prototype.slice.call(fileList);
  }

  document.querySelectorAll('.drop-upload').forEach(function (zone) {
    var input = zone.querySelector('input[type="file"]');
    if (!input) return;

    zone.addEventListener('click', function (event) {
      if (event.target !== input) input.click();
    });

    ['dragenter', 'dragover'].forEach(function (evtName) {
      zone.addEventListener(evtName, function (event) {
        event.preventDefault();
        event.stopPropagation();
        zone.classList.add('is-dragover');
      });
    });

    ['dragleave', 'drop'].forEach(function (evtName) {
      zone.addEventListener(evtName, function (event) {
        event.preventDefault();
        event.stopPropagation();
        zone.classList.remove('is-dragover');
      });
    });

    zone.addEventListener('drop', function (event) {
      var dropped = asFiles(event.dataTransfer && event.dataTransfer.files);
      if (!dropped.length) return;

      var dt = new DataTransfer();
      asFiles(input.files).forEach(function (file) { dt.items.add(file); });
      dropped.forEach(function (file) { dt.items.add(file); });
      input.files = dt.files;
    });
  });

  document.querySelectorAll('input[type="range"][data-range-id]').forEach(function (range) {
    function sync() {
      var target = document.querySelector('[data-range-value="' + range.getAttribute('data-range-id') + '"]');
      if (target) target.textContent = range.value;
    }
    range.addEventListener('input', sync);
    sync();
  });

  document.querySelectorAll('[data-signature-wrapper]').forEach(function (wrapper) {
    var canvas = wrapper.querySelector('[data-signature-canvas]');
    var clearButton = wrapper.querySelector('[data-signature-clear]');
    var hiddenInput = document.getElementById(wrapper.getAttribute('data-input-id'));
    if (!canvas || !hiddenInput) return;

    var ctx = canvas.getContext('2d');
    if (!ctx) return;

    function resizeCanvas() {
      var rect = canvas.getBoundingClientRect();
      var ratio = window.devicePixelRatio || 1;
      canvas.width = Math.max(1, Math.floor(rect.width * ratio));
      canvas.height = Math.max(1, Math.floor(rect.height * ratio));
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.strokeStyle = '#111';
      ctx.clearRect(0, 0, rect.width, rect.height);
      hiddenInput.value = '';
    }

    function getPoint(event) {
      var rect = canvas.getBoundingClientRect();
      return {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top,
      };
    }

    var drawing = false;
    canvas.addEventListener('pointerdown', function (event) {
      event.preventDefault();
      drawing = true;
      var point = getPoint(event);
      ctx.beginPath();
      ctx.moveTo(point.x, point.y);
    });

    canvas.addEventListener('pointermove', function (event) {
      if (!drawing) return;
      event.preventDefault();
      var point = getPoint(event);
      ctx.lineTo(point.x, point.y);
      ctx.stroke();
    });

    function finishDrawing(event) {
      if (!drawing) return;
      if (event) event.preventDefault();
      drawing = false;
      hiddenInput.value = canvas.toDataURL('image/png');
    }

    canvas.addEventListener('pointerup', finishDrawing);
    canvas.addEventListener('pointerleave', finishDrawing);
    canvas.addEventListener('pointercancel', finishDrawing);

    if (clearButton) {
      clearButton.addEventListener('click', function () {
        var rect = canvas.getBoundingClientRect();
        ctx.clearRect(0, 0, rect.width, rect.height);
        hiddenInput.value = '';
      });
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
  });

  var locationSelect = document.querySelector('[data-damage-location]');
  if (locationSelect) {
    locationSelect.addEventListener('change', function () {
      populateDamagePieces();
    });
    populateDamagePieces();
  }

})();
</script>
@endsection

