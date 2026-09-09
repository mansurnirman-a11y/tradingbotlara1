#!/usr/bin/env python3
"""
Capital First - MetaTrader 5 (MT5) Microservice Bridge
Provides a 100% Free, high-speed REST API bridge between MT5 desktop terminal and Laravel Trading Bot.
"""

import sys
import time
import datetime
from flask import Flask, request, jsonify
from flask_cors import CORS

try:
    import MetaTrader5 as mt5
except ImportError:
    print("[ERROR] MetaTrader5 package is not installed. Please run: pip install MetaTrader5")
    sys.exit(1)

app = Flask(__name__)
CORS(app)

def init_mt5():
    """Ensure MT5 is initialized."""
    if not mt5.initialize():
        error = mt5.last_error()
        print(f"[MT5 WARNING] Initialize failed: {error}")
        return False
    return True

# Initialize on startup
init_mt5()

@app.route('/', methods=['GET'])
@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint."""
    is_connected = init_mt5()
    account_info = mt5.account_info() if is_connected else None
    
    return jsonify({
        "status": "online" if (is_connected and account_info) else "disconnected",
        "service": "Capital First MT5 Bridge",
        "account": account_info.login if account_info else None,
        "server": account_info.server if account_info else None,
        "balance": account_info.balance if account_info else 0.0,
        "equity": account_info.equity if account_info else 0.0,
        "currency": account_info.currency if account_info else "USD",
        "company": account_info.company if account_info else None
    })

@app.route('/balance', methods=['GET'])
@app.route('/account', methods=['GET'])
@app.route('/account/balance', methods=['GET'])
@app.route('/api/v1/account/balance', methods=['GET'])
def get_balance():
    """Return account balance, equity, and margin."""
    if not init_mt5():
        return jsonify({"error": "MT5 Terminal not connected", "balance": 0.0, "equity": 0.0}), 503
    
    account_info = mt5.account_info()
    if not account_info:
        return jsonify({"error": "Failed to fetch account info", "balance": 0.0}), 500
    
    currency = account_info.currency or "USD"
    return jsonify({
        "balance": float(account_info.balance),
        "equity": float(account_info.equity),
        "free_margin": float(account_info.margin_free),
        "currency": currency,
        "login": account_info.login,
        "server": account_info.server,
        # CCXT Normalized compatibility
        "USD": {
            "free": float(account_info.margin_free),
            "used": float(account_info.margin),
            "total": float(account_info.balance)
        },
        "USDT": {
            "free": float(account_info.margin_free),
            "used": float(account_info.margin),
            "total": float(account_info.balance)
        }
    })

@app.route('/ohlcv', methods=['GET'])
@app.route('/candles', methods=['GET'])
@app.route('/api/v1/candles', methods=['GET'])
def get_ohlcv():
    """Return historical candlestick data in CCXT format."""
    if not init_mt5():
        return jsonify([]), 503

    symbol = request.args.get('symbol', 'EURUSD').upper().replace('/', '').replace('-', '')
    timeframe_str = request.args.get('timeframe', '15m').lower()
    limit = int(request.args.get('limit', 100))

    tf_map = {
        '1m': mt5.TIMEFRAME_M1,
        '5m': mt5.TIMEFRAME_M5,
        '15m': mt5.TIMEFRAME_M15,
        '30m': mt5.TIMEFRAME_M30,
        '1h': mt5.TIMEFRAME_H1,
        '4h': mt5.TIMEFRAME_H4,
        '1d': mt5.TIMEFRAME_D1,
    }
    mt5_tf = tf_map.get(timeframe_str, mt5.TIMEFRAME_M15)

    # If symbol not found, try adding suffix or search in market watch
    rates = mt5.copy_rates_from_pos(symbol, mt5_tf, 0, limit)
    if rates is None or len(rates) == 0:
        # Try finding symbol with suffixes like .p, .m, .raw
        for s in mt5.symbols_get():
            if symbol in s.name.upper():
                symbol = s.name
                rates = mt5.copy_rates_from_pos(symbol, mt5_tf, 0, limit)
                break

    if rates is None or len(rates) == 0:
        return jsonify([]), 404

    # Format into CCXT format: [[timestamp_ms, open, high, low, close, volume], ...]
    candles = []
    for r in rates:
        candles.append([
            int(r['time']) * 1000,
            float(r['open']),
            float(r['high']),
            float(r['low']),
            float(r['close']),
            float(r['tick_volume'])
        ])

    return jsonify(candles)

@app.route('/ticker', methods=['GET'])
@app.route('/api/v1/ticker', methods=['GET'])
def get_ticker():
    """Return latest symbol price."""
    if not init_mt5():
        return jsonify({"error": "MT5 disconnected"}), 503

    symbol = request.args.get('symbol', 'EURUSD').upper().replace('/', '').replace('-', '')
    
    tick = mt5.symbol_info_tick(symbol)
    if not tick:
        # Try finding matched symbol in market
        for s in mt5.symbols_get():
            if symbol in s.name.upper():
                tick = mt5.symbol_info_tick(s.name)
                symbol = s.name
                break

    if not tick:
        return jsonify({"error": f"Symbol {symbol} not found", "last": 0.0}), 404

    return jsonify({
        "symbol": symbol,
        "bid": float(tick.bid),
        "ask": float(tick.ask),
        "last": float(tick.last or tick.bid),
        "time": int(tick.time) * 1000
    })

@app.route('/positions', methods=['GET'])
@app.route('/open_positions', methods=['GET'])
@app.route('/api/v1/trade/open_positions', methods=['GET'])
def get_positions():
    """Return all currently open positions."""
    if not init_mt5():
        return jsonify([]), 503

    positions = mt5.positions_get()
    if positions is None:
        return jsonify([])

    result = []
    for pos in positions:
        side = "LONG" if pos.type == mt5.ORDER_TYPE_BUY else "SHORT"
        result.append({
            "id": pos.ticket,
            "ticket": pos.ticket,
            "symbol": pos.symbol,
            "side": side,
            "type": side,
            "contracts": float(pos.volume),
            "amount": float(pos.volume),
            "entry_price": float(pos.price_open),
            "entryPrice": float(pos.price_open),
            "current_price": float(pos.price_current),
            "currentPrice": float(pos.price_current),
            "sl": float(pos.sl),
            "tp": float(pos.tp),
            "unrealized_pnl": float(pos.profit),
            "unrealizedPnl": float(pos.profit),
            "profit": float(pos.profit),
            "time": int(pos.time) * 1000
        })

    return jsonify(result)

def resolve_symbol(symbol):
    """Helper to match broker symbols with suffixes (e.g., XAUUSD -> XAUUSD.p)."""
    if not symbol:
        return symbol
    clean = symbol.upper().replace('/', '').replace('-', '').replace('.', '')
    if mt5.symbol_select(symbol, True):
        return symbol
    symbols = mt5.symbols_get()
    if symbols:
        for s in symbols:
            s_clean = s.name.upper().replace('/', '').replace('-', '').replace('.', '')
            if clean == s_clean or s_clean.startswith(clean) or clean in s_clean:
                mt5.symbol_select(s.name, True)
                return s.name
    return symbol

@app.route('/order', methods=['POST'])
@app.route('/trade/spot_order', methods=['POST'])
@app.route('/api/v1/trade/spot_order', methods=['POST'])
def place_order():
    """Place a Market Order in MT5."""
    if not init_mt5():
        return jsonify({"error": "MT5 disconnected"}), 503

    data = request.get_json(force=True, silent=True) or request.form.to_dict()
    raw_symbol = (data.get('symbol') or 'EURUSD').upper()
    symbol = resolve_symbol(raw_symbol)
    side_str = (data.get('side') or 'BUY').upper()
    amount = float(data.get('amount') or data.get('quantity') or 0.01)
    sl = float(data.get('sl') or data.get('stop_loss') or 0.0)
    tp = float(data.get('tp') or data.get('take_profit') or 0.0)

    sym_info = mt5.symbol_info(symbol)
    if not sym_info:
        return jsonify({"error": f"Symbol {raw_symbol} (resolved: {symbol}) not found on broker"}), 404

    # Ensure symbol is active in market watch
    mt5.symbol_select(symbol, True)

    tick = mt5.symbol_info_tick(symbol)
    if not tick:
        return jsonify({"error": f"No price tick available for {symbol}"}), 400

    # Determine order action & price
    if side_str == 'BUY':
        order_type = mt5.ORDER_TYPE_BUY
        price = tick.ask
    else:
        order_type = mt5.ORDER_TYPE_SELL
        price = tick.bid

    # Normalize lot volume to broker limits
    vol = round(amount, 2)
    if sym_info.volume_min and vol < sym_info.volume_min:
        vol = sym_info.volume_min
    if sym_info.volume_max and vol > sym_info.volume_max:
        vol = sym_info.volume_max
    if sym_info.volume_step and sym_info.volume_step > 0:
        vol = round(round(vol / sym_info.volume_step) * sym_info.volume_step, 2)

    # Construct MT5 Trade Request
    trade_request = {
        "action": mt5.TRADE_ACTION_DEAL,
        "symbol": symbol,
        "volume": vol,
        "type": order_type,
        "price": price,
        "deviation": 20,
        "magic": 778899,
        "comment": "CapitalFirst Bot",
        "type_time": mt5.ORDER_TIME_GTC,
        "type_filling": mt5.ORDER_FILLING_IOC,
    }

    if sl > 0:
        trade_request["sl"] = sl
    if tp > 0:
        trade_request["tp"] = tp

    # Send order to MT5 with fallback filling modes
    filling_modes = [mt5.ORDER_FILLING_IOC, mt5.ORDER_FILLING_FOK, mt5.ORDER_FILLING_RETURN]
    result = None
    for fm in filling_modes:
        trade_request["type_filling"] = fm
        result = mt5.order_send(trade_request)
        if result is not None and result.retcode == mt5.TRADE_RETCODE_DONE:
            break

    if result is None or result.retcode != mt5.TRADE_RETCODE_DONE:
        err_msg = f"Order failed: {result.comment if result else mt5.last_error()}"
        return jsonify({"error": err_msg, "retcode": result.retcode if result else -1}), 400

    order_id = str(result.order)
    exec_price = float(result.price or price)

    return jsonify({
        "id": order_id,
        "order_id": order_id,
        "symbol": symbol,
        "side": side_str,
        "type": "market",
        "price": exec_price,
        "average": exec_price,
        "amount": vol,
        "quantity": vol,
        "status": "closed",
        "broker_response": f"Executed on MT5 (Ticket #{order_id})"
    })

@app.route('/close', methods=['POST'])
@app.route('/close_position', methods=['POST'])
@app.route('/trade/close_position', methods=['POST'])
@app.route('/api/v1/trade/close_position', methods=['POST'])
def close_position():
    """Close an open position in MT5."""
    if not init_mt5():
        return jsonify({"error": "MT5 disconnected"}), 503

    data = request.get_json(force=True, silent=True) or request.form.to_dict()
    ticket_id = data.get('ticket') or data.get('position_id') or data.get('id')
    symbol = (data.get('symbol') or '').upper().replace('/', '').replace('-', '')

    open_positions = mt5.positions_get()
    target_pos = None

    if open_positions:
        for pos in open_positions:
            if ticket_id and str(pos.ticket) == str(ticket_id):
                target_pos = pos
                break
            elif symbol and symbol in pos.symbol.upper():
                target_pos = pos
                break

    if not target_pos:
        return jsonify({"status": "already_closed", "message": "Position not found or already closed."})

    # Close opposite trade
    close_type = mt5.ORDER_TYPE_SELL if target_pos.type == mt5.ORDER_TYPE_BUY else mt5.ORDER_TYPE_BUY
    price = mt5.symbol_info_tick(target_pos.symbol).bid if close_type == mt5.ORDER_TYPE_SELL else mt5.symbol_info_tick(target_pos.symbol).ask

    close_req = {
        "action": mt5.TRADE_ACTION_DEAL,
        "symbol": target_pos.symbol,
        "volume": float(target_pos.volume),
        "type": close_type,
        "position": target_pos.ticket,
        "price": price,
        "deviation": 20,
        "magic": 778899,
        "comment": "Close CapitalFirst Bot",
        "type_time": mt5.ORDER_TIME_GTC,
        "type_filling": mt5.ORDER_FILLING_IOC,
    }

    filling_modes = [mt5.ORDER_FILLING_IOC, mt5.ORDER_FILLING_FOK, mt5.ORDER_FILLING_RETURN]
    result = None
    for fm in filling_modes:
        close_req["type_filling"] = fm
        result = mt5.order_send(close_req)
        if result is not None and result.retcode == mt5.TRADE_RETCODE_DONE:
            break

    if result is None or result.retcode != mt5.TRADE_RETCODE_DONE:
        return jsonify({"error": f"Close failed: {result.comment if result else mt5.last_error()}"}), 400

    return jsonify({
        "status": "closed",
        "ticket": target_pos.ticket,
        "close_price": float(result.price or price),
        "profit": float(target_pos.profit)
    })

if __name__ == '__main__':
    port = 5000
    print(f"==================================================")
    print(f"  Capital First - MetaTrader 5 Microservice Bridge")
    print(f"  Listening on: http://127.0.0.1:{port}")
    print(f"==================================================")
    app.run(host='0.0.0.0', port=port, debug=False)
