import sys
import json
import pandas as pd
import joblib
from datetime import timedelta

# 从 PHP 接收 JSON 输入（历史交易记录）
input_json = sys.stdin.read()
recent_transactions = json.loads(input_json)  # list

# 加载模型
model = joblib.load("model/vendor_income_model.pkl")

# 转 DataFrame
df = pd.DataFrame(recent_transactions)
df['CreatedAt'] = pd.to_datetime(df['CreatedAt'])

# 获取最后一条交易时间
last_time = df['CreatedAt'].max()

# 构造下一天的时间范围（假设每小时一个样本）
future_hours = pd.date_range(
    start=last_time + timedelta(hours=1),
    end=last_time + timedelta(days=1),
    freq='H'
)

# 用最近一条交易的 vendor 信息填充未来的特征
last_record = df.iloc[-1]
future_df = pd.DataFrame({
    'SenderID': [last_record['SenderID']] * len(future_hours),
    'SenderType': [last_record['SenderType']] * len(future_hours),
    'ReceiverType': [last_record['ReceiverType']] * len(future_hours),
    'Hour': future_hours.hour,
    'Weekday': future_hours.weekday
})

# One-hot 编码
future_df = pd.get_dummies(future_df)

# 补齐缺失列
expected_cols = model.feature_names_in_
for col in expected_cols:
    if col not in future_df.columns:
        future_df[col] = 0
future_df = future_df[expected_cols]

# 预测未来每小时收入
predictions = model.predict(future_df)

# 计算总收入
total_income = round(predictions.sum(), 2)

print(json.dumps({
    "success": True,
    "predicted_date": (last_time + timedelta(days=1)).strftime('%Y-%m-%d'),
    "predicted_total_income": total_income,
    "hourly_predictions": [
        {"hour": int(future_hours[i].hour), "predicted": round(pred, 2)}
        for i, pred in enumerate(predictions)
    ]
}))
