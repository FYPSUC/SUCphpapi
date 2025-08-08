import sys
import json
import pandas as pd
import joblib

# 从 PHP 接收 JSON 输入（多条交易记录）
input_json = sys.stdin.read()
recent_transactions = json.loads(input_json)  # 应该是一个 list

# 加载模型
model = joblib.load("model/transaction_model.pkl")

# 转为 DataFrame 并预处理
df = pd.DataFrame(recent_transactions)
df['CreatedAt'] = pd.to_datetime(df['CreatedAt'])
df['Hour'] = df['CreatedAt'].dt.hour
df['Weekday'] = df['CreatedAt'].dt.weekday

# 构建特征
df_features = df[['SenderID', 'SenderType', 'ReceiverType', 'Hour', 'Weekday']]
df_features = pd.get_dummies(df_features)

# 补齐缺失的列（确保与模型训练时一致）
expected_cols = model.feature_names_in_
for col in expected_cols:
    if col not in df_features.columns:
        df_features[col] = 0
df_features = df_features[expected_cols]

# 按小时分组预测
df['prediction'] = model.predict(df_features)

hourly = df.groupby('Hour')['prediction'].mean().reset_index()
hourly_predictions = [
    {"hour": int(row['Hour']), "predicted": round(row['prediction'], 2)}
    for _, row in hourly.iterrows()
]

# 输出 JSON 给 PHP
print(json.dumps({
    "success": True,
    "hourly_predictions": hourly_predictions
}))
