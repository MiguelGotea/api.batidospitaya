/**
 * indicators_deferred.js
 * Carga asíncrona por lotes de indicadores del core con reintento automático y manual.
 */

$(document).ready(function () {
    const indicatorsToLoad = [];
    
    // 1. Detectar todos los indicadores diferidos
    $('[data-indicator-codigo]').each(function () {
        const codigo = $(this).data('indicator-codigo');
        if (codigo) {
            indicatorsToLoad.push(codigo);
            // Mostrar spinner de carga inicial
            setIndicatorLoading($(this));
        }
    });

    if (indicatorsToLoad.length === 0) return;

    // 2. Realizar la carga por lote (batch)
    cargarIndicadoresEnLote(indicatorsToLoad);
});

/**
 * Pone un indicador en estado de carga (spinner/pulse)
 */
function setIndicatorLoading($card) {
    $card.addClass('indicator-loading');
    
    // Guardar el icono original por si se necesita restaurar
    const $icon = $card.find('.indicator-icon i');
    if ($icon.length && !$card.data('original-icon-class')) {
        $card.data('original-icon-class', $icon.attr('class'));
        // Poner spinner en el icono principal
        $icon.attr('class', 'fas fa-spinner fa-spin');
    }
    
    // Guardar contenido original de valor/fecha por si acaso
    if (!$card.data('original-count-html')) {
        $card.data('original-count-html', $card.find('.indicator-count').html());
    }

    $card.find('.indicator-count').html('<span class="shimmer-text">...</span>');
    $card.find('.indicator-status').html('<i class="fas fa-sync-alt fa-spin"></i> Cargando...').attr('class', 'indicator-status gris');
}

/**
 * Carga un lote de indicadores mediante POST
 */
function cargarIndicadoresEnLote(codigos) {
    $.ajax({
        url: '../../core/components/ajax/load_indicators_batch.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ codigos: codigos }),
        dataType: 'json',
        success: function (response) {
            if (response.success && response.indicadores) {
                // Procesar cada indicador retornado
                for (const codigo in response.indicadores) {
                    const data = response.indicadores[codigo];
                    const $card = $('[data-indicator-codigo="' + codigo + '"]');
                    
                    if ($card.length) {
                        if (data.success) {
                            actualizarTarjetaIndicador($card, data);
                        } else {
                            // Si falló el cálculo en el servidor, intentar reintento automático
                            iniciarReintentoAutomatico($card, codigo);
                        }
                    }
                }
            } else {
                // Si falla la petición general, reintentar todos de forma individual
                $('[data-indicator-codigo]').each(function () {
                    const codigo = $(this).data('indicator-codigo');
                    iniciarReintentoAutomatico($(this), codigo);
                });
            }
        },
        error: function () {
            // Error de red/servidor: reintentar todos individualmente
            $('[data-indicator-codigo]').each(function () {
                const codigo = $(this).data('indicator-codigo');
                iniciarReintentoAutomatico($(this), codigo);
            });
        }
    });
}

/**
 * Ejecuta un reintento automático individual pasados 2 segundos
 */
function iniciarReintentoAutomatico($card, codigo) {
    $card.find('.indicator-status').html('<i class="fas fa-exclamation-triangle"></i> Reintentando...').attr('class', 'indicator-status amarillo');
    
    setTimeout(function () {
        cargarIndicadorIndividual($card, codigo, false); // false = es automático, no manual
    }, 2000);
}

/**
 * Carga un indicador de forma individual
 */
function cargarIndicadorIndividual($card, codigo, isManual = false) {
    setIndicatorLoading($card);
    if (isManual) {
        $card.find('.indicator-status').html('<i class="fas fa-sync-alt fa-spin"></i> Conectando...').attr('class', 'indicator-status gris');
    }

    $.ajax({
        url: '../../core/components/ajax/refresh_indicator.php',
        method: 'GET',
        data: { codigo: codigo },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                actualizarTarjetaIndicador($card, response);
            } else {
                setIndicatorError($card, codigo);
            }
        },
        error: function () {
            setIndicatorError($card, codigo);
        }
    });
}

/**
 * Pone un indicador en estado de error con opción de reintento manual
 */
function setIndicatorError($card, codigo) {
    $card.removeClass('indicator-loading');
    
    // Restaurar icono original
    const origIcon = $card.data('original-icon-class');
    if (origIcon) {
        $card.find('.indicator-icon i').attr('class', origIcon);
    }
    
    $card.find('.indicator-count').html('<span style="font-size: 1.8rem; color: #dc3545;">⚠️ Error</span>');
    
    // Mostrar estado de error con botón de reintento manual
    const $status = $card.find('.indicator-status');
    $status.html('Carga fallida. <a href="javascript:void(0)" class="btn-retry-indicator" style="text-decoration: underline; color: #721c24; font-weight: bold; margin-left: 5px;">Reintentar ↻</a>')
           .attr('class', 'indicator-status rojo');

    // Desvincular eventos anteriores y vincular el botón de reintento
    $card.find('.btn-retry-indicator').off('click').on('click', function (e) {
        e.stopPropagation(); // Evitar que el clic en el botón dispare el modal de la tarjeta
        cargarIndicadorIndividual($card, codigo, true);
    });
}

/**
 * Actualiza la tarjeta con los datos exitosos del indicador
 */
function actualizarTarjetaIndicador($card, data) {
    $card.removeClass('indicator-loading');
    
    // Restaurar icono original si estaba girando
    const origIcon = $card.data('original-icon-class');
    if (origIcon) {
        $card.find('.indicator-icon i').attr('class', origIcon);
    }

    // Actualizar valor
    $card.find('.indicator-count').text(data.valor);

    // Actualizar URL de enlace si la tarjeta está envuelta por una etiqueta a
    const $a = $card.closest('a');
    if ($a.length && data.url) {
        $a.attr('href', data.url);
    }

    // Actualizar color y texto del badge de estado
    const $status = $card.find('.indicator-status');
    $status.attr('class', 'indicator-status ' + data.codigo + '-indicador ' + data.color);
    
    if (data.dias_restantes !== undefined && data.dias_restantes !== null) {
        const dias = parseInt(data.dias_restantes);
        const total = parseInt(data.valor);
        
        if (total === 0) {
            $status.text('Al día');
        } else if (dias < 0) {
            $status.text('Vencido hace ' + Math.abs(dias) + ' días');
        } else if (dias === 0) {
            $status.text('Vence hoy');
        } else {
            $status.text(dias + ' días restantes');
        }
    } else {
        $status.text(data.fecha_limite || 'Sin fecha');
    }
}
